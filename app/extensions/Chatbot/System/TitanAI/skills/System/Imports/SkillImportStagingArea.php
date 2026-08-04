<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Imports;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class SkillImportStagingArea
{
    private string $basePath;

    public function __construct(?string $basePath = null, private readonly int $ttlMinutes = 60)
    {
        $this->basePath = rtrim($basePath ?? storage_path('app'), DIRECTORY_SEPARATOR);
    }

    /** @param array<int, array{path: string, content: string}> $files */
    public function stage(array $files): SkillStagedImport
    {
        $this->purgeStale();
        $id = bin2hex(random_bytes(16));
        $path = $this->stagingRoot() . DIRECTORY_SEPARATOR . $id;
        $this->makeDirectory($path);

        try {
            foreach ($files as $file) {
                $relative = str_replace('/', DIRECTORY_SEPARATOR, (string) $file['path']);
                $target = $path . DIRECTORY_SEPARATOR . $relative;
                $this->makeDirectory(dirname($target));
                if (file_put_contents($target, (string) $file['content'], LOCK_EX) === false) {
                    throw new RuntimeException("Failed to stage skill file: {$file['path']}");
                }
            }
        } catch (\Throwable $exception) {
            $this->deleteDirectory($path);
            throw $exception;
        }

        return new SkillStagedImport($id, $path);
    }

    public function promote(SkillStagedImport $staged, string $finalPath): void
    {
        $this->assertManagedPath($staged->path);
        $this->assertManagedPath($finalPath);

        if (! is_dir($staged->path)) {
            throw new RuntimeException('The staged skill package no longer exists.');
        }
        if (file_exists($finalPath)) {
            throw new RuntimeException('The target skill storage directory already exists.');
        }

        $this->makeDirectory(dirname($finalPath));
        if (! rename($staged->path, $finalPath)) {
            throw new RuntimeException('Failed to atomically promote the staged skill package.');
        }
    }

    public function finalSkillPath(int|string|null $ownerScope, string $slug): string
    {
        $owner = $ownerScope === null ? 'public' : (string) $ownerScope;
        if (! preg_match('/^(?:[1-9][0-9]*|company-[1-9][0-9]*|public)$/', $owner)) {
            throw new RuntimeException('Invalid skill storage owner scope.');
        }
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new RuntimeException('Invalid skill storage slug.');
        }

        return $this->basePath . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . $owner . DIRECTORY_SEPARATOR . $slug;
    }

    /** @return array<string, array<int, array{name: string, path: string, size: int, sha256: string, mime_type: string|null}>> */
    public function describeResources(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $resources = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $absolute = $file->getPathname();
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($absolute, strlen($directory) + 1));
            if (in_array(strtoupper($relative), ['SKILL.MD', 'PACKAGE-MANIFEST.JSON'], true)) {
                continue;
            }

            $root = explode('/', $relative)[0];
            $hash = hash_file('sha256', $absolute);
            $resources[$root][] = [
                'name' => basename($relative),
                'path' => $relative,
                'size' => filesize($absolute) ?: 0,
                'sha256' => is_string($hash) ? $hash : hash('sha256', ''),
                'mime_type' => $this->detectMimeType($absolute),
            ];
        }

        ksort($resources, SORT_STRING);
        foreach ($resources as &$entries) {
            usort($entries, static fn (array $a, array $b): int => strcmp($a['path'], $b['path']));
        }
        unset($entries);

        return $resources;
    }

    public function deleteDirectory(string $path): void
    {
        if (! file_exists($path) && ! is_link($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($path);
    }

    public function purgeStale(): int
    {
        $root = $this->stagingRoot();
        if (! is_dir($root)) {
            return 0;
        }

        $cutoff = time() - max(1, $this->ttlMinutes) * 60;
        $removed = 0;
        foreach (new FilesystemIterator($root, FilesystemIterator::SKIP_DOTS) as $entry) {
            if ($entry->isDir() && $entry->getMTime() < $cutoff) {
                $this->deleteDirectory($entry->getPathname());
                $removed++;
            }
        }

        return $removed;
    }

    private function stagingRoot(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . '.staging';
    }

    private function makeDirectory(string $path): void
    {
        if (! is_dir($path) && ! mkdir($path, 0750, true) && ! is_dir($path)) {
            throw new RuntimeException("Failed to create directory: {$path}");
        }
    }

    private function assertManagedPath(string $path): void
    {
        $base = str_replace('\\', '/', $this->basePath);
        $candidate = str_replace('\\', '/', $path);
        if ($candidate !== $base && ! str_starts_with($candidate, $base . '/')) {
            throw new RuntimeException('Skill import path is outside managed storage.');
        }
    }

    private function detectMimeType(string $path): ?string
    {
        if (! class_exists(\finfo::class)) {
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);

        return is_string($mime) ? $mime : null;
    }
}
