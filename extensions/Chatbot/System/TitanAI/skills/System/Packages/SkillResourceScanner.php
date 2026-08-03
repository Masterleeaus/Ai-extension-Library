<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Packages;

use RuntimeException;

final class SkillResourceScanner
{
    /**
     * @param array<string, mixed>|null $bundledResources
     * @return list<SkillResourceFingerprint>
     */
    public function scan(string $basePath, ?array $bundledResources): array
    {
        if ($bundledResources === null || $bundledResources === []) {
            return [];
        }

        $baseRealPath = realpath($basePath);
        if ($baseRealPath === false || ! is_dir($baseRealPath)) {
            throw new RuntimeException('Skill resource directory does not exist.');
        }

        $paths = $this->resourcePaths($bundledResources);
        $fingerprints = [];

        foreach ($paths as $relativePath) {
            $normalised = SkillResourceFingerprint::normalisePath($relativePath);
            $fullPath = realpath($baseRealPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalised));
            if ($fullPath === false || ! str_starts_with($fullPath, $baseRealPath . DIRECTORY_SEPARATOR) || ! is_file($fullPath)) {
                throw new RuntimeException("Bundled resource [{$normalised}] is missing or outside the skill directory.");
            }

            $content = file_get_contents($fullPath);
            if ($content === false) {
                throw new RuntimeException("Bundled resource [{$normalised}] could not be read.");
            }

            $mimeType = null;
            if (class_exists(\finfo::class)) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $detected = $finfo->file($fullPath);
                $mimeType = is_string($detected) ? $detected : null;
            }

            $fingerprints[] = SkillResourceFingerprint::fromContent($normalised, $content, $mimeType);
        }

        usort($fingerprints, static fn (SkillResourceFingerprint $a, SkillResourceFingerprint $b): int => strcmp($a->path, $b->path));

        return $fingerprints;
    }

    /** @param array<string, mixed> $bundledResources @return list<string> */
    private function resourcePaths(array $bundledResources): array
    {
        $paths = [];
        foreach ($bundledResources as $folder => $files) {
            if (! is_array($files)) {
                continue;
            }
            foreach ($files as $file) {
                $path = is_array($file)
                    ? ($file['path'] ?? $file['name'] ?? null)
                    : $file;
                if (! is_string($path) || trim($path) === '') {
                    continue;
                }
                if (! str_contains($path, '/') && is_string($folder) && $folder !== '') {
                    $path = trim($folder, '/') . '/' . $path;
                }
                $paths[] = SkillResourceFingerprint::normalisePath($path);
            }
        }

        $paths = array_values(array_unique($paths));
        sort($paths, SORT_STRING);

        return $paths;
    }
}
