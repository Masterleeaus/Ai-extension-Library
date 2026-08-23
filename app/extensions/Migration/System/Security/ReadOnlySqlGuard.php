<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Security;

use InvalidArgumentException;

final class ReadOnlySqlGuard
{
    public function assertReadOnly(string $sql): string
    {
        $trimmed = trim($sql);
        if ($trimmed === '') {
            throw new InvalidArgumentException('SQL query cannot be empty.');
        }

        $analysis = $this->normaliseForAnalysis($trimmed);
        $withoutFinalSemicolon = rtrim($analysis);
        if (str_ends_with($withoutFinalSemicolon, ';')) {
            $withoutFinalSemicolon = rtrim(substr($withoutFinalSemicolon, 0, -1));
        }

        if (str_contains($withoutFinalSemicolon, ';')) {
            throw new InvalidArgumentException('Multiple SQL statements are not allowed.');
        }

        if (! preg_match('/^(SELECT\b|WITH\b)/i', ltrim($withoutFinalSemicolon))) {
            throw new InvalidArgumentException('Only SELECT statements and read-only CTEs are allowed.');
        }

        if (preg_match('/\b(INSERT|UPDATE|DELETE|UPSERT|REPLACE|MERGE|DROP|ALTER|CREATE|TRUNCATE|RENAME|CALL|EXEC(?:UTE)?|GRANT|REVOKE|LOCK|UNLOCK|LOAD|COPY|VACUUM|ATTACH|DETACH|PRAGMA)\b/i', $withoutFinalSemicolon) === 1) {
            throw new InvalidArgumentException('SQL contains a write or administrative operation.');
        }

        if (preg_match('/\bFOR\s+UPDATE\b|\bLOCK\s+IN\s+SHARE\s+MODE\b|\bINTO\s+(?:OUTFILE|DUMPFILE)\b/i', $withoutFinalSemicolon) === 1) {
            throw new InvalidArgumentException('SQL contains a locking or file-output clause.');
        }

        return $trimmed;
    }

    private function normaliseForAnalysis(string $sql): string
    {
        $withoutBlockComments = preg_replace('~/\*.*?\*/~s', ' ', $sql) ?? $sql;
        $withoutLineComments = preg_replace('/(?:--|#)[^\r\n]*/', ' ', $withoutBlockComments) ?? $withoutBlockComments;
        $withoutSingleStrings = preg_replace("/'(?:''|\\\\.|[^'])*'/s", "''", $withoutLineComments) ?? $withoutLineComments;
        $withoutDoubleStrings = preg_replace('/"(?:""|\\\\.|[^"])*"/s', '""', $withoutSingleStrings) ?? $withoutSingleStrings;

        return preg_replace('/\s+/', ' ', $withoutDoubleStrings) ?? $withoutDoubleStrings;
    }
}
