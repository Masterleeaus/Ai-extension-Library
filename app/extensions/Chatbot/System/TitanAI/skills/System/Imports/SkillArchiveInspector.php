<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Imports;

use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

final class SkillArchiveInspector
{
    public function __construct(
        private readonly SkillImportSecurityPolicy $policy,
        private readonly SkillImportFileSetInspector $fileSetInspector,
    ) {
    }

    /**
     * @return array{files: array<int, array<string, mixed>>, bundled_resources: array<string, array<int, array<string, mixed>>>, package_manifest: array<string, mixed>|null, import_report: array<string, mixed>}
     */
    public function inspect(string $archivePath): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP ZIP extension is required to import skill archives.');
        }

        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Failed to open skill archive.');
        }

        try {
            $maxEntries = (int) config('system-ai-chat-skills.imports.max_archive_entries', 250);
            $maxBytes = (int) config('system-ai-chat-skills.imports.max_uncompressed_bytes', 25 * 1024 * 1024);
            $maxRatio = (float) config('system-ai-chat-skills.imports.max_compression_ratio', 100.0);
            $maxInstructionBytes = (int) config('system-ai-chat-skills.imports.max_instruction_bytes', 1_048_576);

            if ($zip->numFiles > $maxEntries) {
                throw new InvalidArgumentException('The skill archive contains too many files.');
            }

            $entries = [];
            $seenArchivePaths = [];
            $skillPaths = [];
            $totalUncompressed = 0;
            $totalCompressed = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $rawName = $zip->getNameIndex($i, ZipArchive::FL_UNCHANGED);
                if (! is_string($rawName)) {
                    throw new InvalidArgumentException('The skill archive contains an unreadable entry name.');
                }

                $directory = str_ends_with($rawName, '/');
                $normalisedArchivePath = $this->policy->normaliseArchivePath($rawName, $directory);
                $duplicateKey = $this->policy->duplicateKey($normalisedArchivePath);
                if (isset($seenArchivePaths[$duplicateKey])) {
                    throw new InvalidArgumentException("Duplicate skill archive entry: {$normalisedArchivePath}");
                }
                $seenArchivePaths[$duplicateKey] = true;

                $operatingSystem = 0;
                $externalAttributes = 0;
                if ($zip->getExternalAttributesIndex($i, $operatingSystem, $externalAttributes, ZipArchive::FL_UNCHANGED)) {
                    $fileType = ($externalAttributes >> 16) & 0xF000;
                    if ($fileType === 0xA000) {
                        throw new InvalidArgumentException("Symbolic links are not allowed in skill archives: {$normalisedArchivePath}");
                    }
                }

                $stat = $zip->statIndex($i, ZipArchive::FL_UNCHANGED);
                $uncompressedSize = (int) ($stat['size'] ?? 0);
                $compressedSize = (int) ($stat['comp_size'] ?? 0);
                $totalUncompressed += $uncompressedSize;
                $totalCompressed += $compressedSize;

                if ($totalUncompressed > $maxBytes) {
                    throw new InvalidArgumentException('The expanded skill archive is too large.');
                }

                if (! $directory && $uncompressedSize > 0) {
                    $ratio = $uncompressedSize / max(1, $compressedSize);
                    if ($ratio > $maxRatio) {
                        throw new InvalidArgumentException("Archive compression ratio is unsafe for {$normalisedArchivePath}.");
                    }
                }

                if (! $directory && strtoupper(basename($normalisedArchivePath)) === 'SKILL.MD') {
                    $skillPaths[] = $normalisedArchivePath;
                    if ($uncompressedSize > $maxInstructionBytes) {
                        throw new InvalidArgumentException('SKILL.md exceeds the configured instruction size limit.');
                    }
                }

                $entries[] = [
                    'index' => $i,
                    'archive_path' => $normalisedArchivePath,
                    'directory' => $directory,
                    'uncompressed_size' => $uncompressedSize,
                    'compressed_size' => $compressedSize,
                ];
            }

            if ($skillPaths === []) {
                throw new InvalidArgumentException('No SKILL.md file found in the skill archive.');
            }
            if (count($skillPaths) > 1) {
                throw new InvalidArgumentException('A skill archive must not contain more than one SKILL.md file.');
            }

            $skillPath = $skillPaths[0];
            $prefix = dirname($skillPath);
            if ($prefix === '.') {
                $prefix = '';
            } elseif (str_contains($prefix, '/')) {
                throw new InvalidArgumentException('SKILL.md may be at archive root or inside one package folder only.');
            }
            $prefixWithSlash = $prefix === '' ? '' : $prefix . '/';

            $files = [];
            foreach ($entries as $entry) {
                $archiveEntryPath = $entry['archive_path'];
                if ($prefixWithSlash !== '') {
                    if ($entry['directory'] && $archiveEntryPath === $prefix) {
                        continue;
                    }
                    if (! str_starts_with($archiveEntryPath, $prefixWithSlash)) {
                        throw new InvalidArgumentException('All archive entries must be inside the detected skill package folder.');
                    }
                    $relativePath = substr($archiveEntryPath, strlen($prefixWithSlash));
                } else {
                    $relativePath = $archiveEntryPath;
                }

                if ($relativePath === '') {
                    continue;
                }

                $relativePath = $this->policy->normalisePackagePath($relativePath, $entry['directory']);
                if ($entry['directory']) {
                    continue;
                }

                $content = $zip->getFromIndex($entry['index']);
                if (! is_string($content)) {
                    throw new InvalidArgumentException("Could not read archive entry: {$relativePath}");
                }

                $files[] = ['path' => $relativePath, 'content' => $content];
            }

            $totalRatio = $totalUncompressed / max(1, $totalCompressed);
            if ($totalUncompressed > 0 && $totalRatio > $maxRatio) {
                throw new InvalidArgumentException('The total skill archive compression ratio is unsafe.');
            }

            return $this->fileSetInspector->inspect($files, [
                'source' => 'archive',
                'archive_entries' => count($entries),
                'uncompressed_bytes' => $totalUncompressed,
                'compressed_bytes' => $totalCompressed,
                'compression_ratio' => round($totalRatio, 2),
                'package_prefix' => $prefix,
            ]);
        } finally {
            $zip->close();
        }
    }
}
