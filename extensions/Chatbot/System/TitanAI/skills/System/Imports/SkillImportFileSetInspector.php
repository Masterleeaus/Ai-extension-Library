<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Imports;

use InvalidArgumentException;
use JsonException;

final class SkillImportFileSetInspector
{
    public function __construct(private readonly SkillImportSecurityPolicy $policy)
    {
    }

    /**
     * @param array<int, array{path: string, content: string}> $files
     * @param array<string, mixed> $report
     * @return array{files: array<int, array{path: string, content: string, size: int, sha256: string, mime_type: string|null}>, bundled_resources: array<string, array<int, array{name: string, path: string, size: int, sha256: string, mime_type: string|null}>>, package_manifest: array<string, mixed>|null, import_report: array<string, mixed>}
     */
    public function inspect(array $files, array $report = []): array
    {
        $maxEntries = function_exists('config')
            ? (int) config('system-ai-chat-skills.imports.max_archive_entries', 250)
            : 250;
        $maxBytes = function_exists('config')
            ? (int) config('system-ai-chat-skills.imports.max_uncompressed_bytes', 25 * 1024 * 1024)
            : 25 * 1024 * 1024;
        $maxInstructionBytes = function_exists('config')
            ? (int) config('system-ai-chat-skills.imports.max_instruction_bytes', 1024 * 1024)
            : 1024 * 1024;

        if (count($files) > $maxEntries) {
            throw new InvalidArgumentException('The skill package contains too many files.');
        }

        $normalisedFiles = [];
        $bundledResources = [];
        $seen = [];
        $skillCount = 0;
        $packageManifest = null;
        $totalBytes = 0;

        foreach ($files as $file) {
            if (! is_array($file) || ! is_string($file['path'] ?? null) || ! is_string($file['content'] ?? null)) {
                throw new InvalidArgumentException('Every imported skill file must provide a string path and content.');
            }

            $metadata = $this->policy->validateContent($file['path'], $file['content']);
            $path = $metadata['path'];
            $key = $this->policy->duplicateKey($path);

            if (isset($seen[$key])) {
                throw new InvalidArgumentException("Duplicate skill package entry: {$path}");
            }
            $seen[$key] = true;

            if ($path === 'SKILL.md') {
                $skillCount++;
                if ($metadata['size'] > $maxInstructionBytes) {
                    throw new InvalidArgumentException('SKILL.md exceeds the configured instruction size limit.');
                }
            } elseif ($path === 'PACKAGE-MANIFEST.json') {
                try {
                    $decoded = json_decode($file['content'], true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new InvalidArgumentException('PACKAGE-MANIFEST.json must contain valid JSON.', 0, $exception);
                }
                $packageManifest = is_array($decoded) ? $decoded : null;
            } else {
                $root = explode('/', $path)[0];
                $bundledResources[$root][] = [
                    'name' => basename($path),
                    'path' => $path,
                    'size' => $metadata['size'],
                    'sha256' => $metadata['sha256'],
                    'mime_type' => $metadata['mime_type'],
                ];
            }

            $normalisedFiles[] = [
                'path' => $path,
                'content' => $file['content'],
                'size' => $metadata['size'],
                'sha256' => $metadata['sha256'],
                'mime_type' => $metadata['mime_type'],
            ];
            $totalBytes += $metadata['size'];
            if ($totalBytes > $maxBytes) {
                throw new InvalidArgumentException('The expanded skill package is too large.');
            }
        }

        if ($skillCount === 0) {
            throw new InvalidArgumentException('No SKILL.md file was supplied.');
        }
        if ($skillCount > 1) {
            throw new InvalidArgumentException('A skill package must not contain more than one SKILL.md file.');
        }

        usort($normalisedFiles, static fn (array $a, array $b): int => strcmp($a['path'], $b['path']));
        ksort($bundledResources, SORT_STRING);
        foreach ($bundledResources as &$resources) {
            usort($resources, static fn (array $a, array $b): int => strcmp($a['path'], $b['path']));
        }
        unset($resources);

        return [
            'files' => $normalisedFiles,
            'bundled_resources' => $bundledResources,
            'package_manifest' => $packageManifest,
            'import_report' => array_merge($report, [
                'accepted_files' => count($normalisedFiles),
                'accepted_bytes' => $totalBytes,
                'resource_files' => array_sum(array_map('count', $bundledResources)),
                'paths' => array_column($normalisedFiles, 'path'),
            ]),
        ];
    }
}
