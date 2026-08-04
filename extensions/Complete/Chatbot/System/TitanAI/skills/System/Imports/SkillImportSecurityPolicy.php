<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Imports;

use InvalidArgumentException;
use JsonException;

final class SkillImportSecurityPolicy
{
    /** @var list<string> */
    private array $allowedRoots;

    /** @var array<string, list<string>> */
    private array $allowedExtensions;

    /** @var list<string> */
    private array $dangerousExtensions = [
        'exe', 'bat', 'cmd', 'com', 'scr', 'pif', 'vbs', 'vbe', 'wsf', 'wsh',
        'ps1', 'psm1', 'msi', 'msp', 'jar', 'war', 'dll', 'so', 'dylib', 'bin',
        'elf', 'deb', 'rpm', 'phar', 'php', 'php3', 'php4', 'php5', 'phtml',
        'htaccess', 'htpasswd', 'env', 'pem', 'key', 'crt', 'cer', 'pfx', 'p12',
    ];

    /**
     * @param list<string> $allowedRoots
     * @param array<string, list<string>>|null $allowedExtensions
     */
    public function __construct(
        array $allowedRoots = ['scripts', 'references', 'assets'],
        private readonly int $maxDepth = 5,
        private readonly int $maxFileBytes = 2_097_152,
        ?array $allowedExtensions = null,
    ) {
        $this->allowedRoots = array_values(array_unique(array_map(
            static fn (string $root): string => strtolower(trim($root)),
            $allowedRoots,
        )));
        $this->allowedExtensions = $allowedExtensions ?? [
            'scripts' => ['js', 'mjs', 'cjs', 'ts', 'py', 'sh', 'sql'],
            'references' => ['md', 'markdown', 'txt', 'json', 'yaml', 'yml', 'csv', 'xml', 'html', 'htm', 'pdf'],
            'assets' => ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'css', 'json', 'txt'],
        ];
    }

    public static function fromConfig(): self
    {
        $extensions = config('system-ai-chat-skills.imports.allowed_extensions');

        return new self(
            allowedRoots: (array) config('system-ai-chat-skills.imports.allowed_resource_directories', ['scripts', 'references', 'assets']),
            maxDepth: (int) config('system-ai-chat-skills.imports.max_directory_depth', 5),
            maxFileBytes: (int) config('system-ai-chat-skills.imports.max_file_bytes', 2_097_152),
            allowedExtensions: is_array($extensions) && $extensions !== [] ? $extensions : null,
        );
    }

    /**
     * Normalise an archive path before the optional package-folder prefix is removed.
     */
    public function normaliseArchivePath(string $path, bool $directory = false): string
    {
        $path = $this->normaliseUnicode($path);

        if ($path === '' || preg_match('/[\x00-\x1F\x7F]/u', $path) === 1) {
            throw new InvalidArgumentException('Archive paths must not be empty or contain control characters.');
        }

        if (str_contains($path, '\\')) {
            throw new InvalidArgumentException('Backslashes are not allowed in skill package paths.');
        }

        if (str_starts_with($path, '/') || str_starts_with($path, '//') || preg_match('/^[A-Za-z]:\//', $path) === 1) {
            throw new InvalidArgumentException('Absolute skill package paths are not allowed.');
        }

        if (preg_match('/%(?:2e|2f|5c)/i', $path) === 1) {
            throw new InvalidArgumentException('Encoded traversal characters are not allowed in skill package paths.');
        }

        $path = $directory ? rtrim($path, '/') : $path;
        if ($path === '') {
            throw new InvalidArgumentException('Archive root entries are not allowed.');
        }

        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new InvalidArgumentException('Skill package paths must not contain empty or traversal segments.');
            }

            if (str_starts_with($segment, '.')) {
                throw new InvalidArgumentException('Hidden skill package files and folders are not allowed.');
            }
        }

        if (count($segments) - 1 > $this->maxDepth + 1) {
            throw new InvalidArgumentException('Skill package paths exceed the configured directory depth.');
        }

        return implode('/', $segments);
    }

    /**
     * Normalise a path relative to the package root and enforce supported roots.
     */
    public function normalisePackagePath(string $path, bool $directory = false): string
    {
        $path = $this->normaliseArchivePath($path, $directory);
        $segments = explode('/', $path);

        if (count($segments) === 1) {
            if ($directory) {
                $root = strtolower($segments[0]);
                if (! in_array($root, $this->allowedRoots, true)) {
                    throw new InvalidArgumentException('Unsupported root directory in skill package.');
                }

                return $root;
            }

            return match (strtoupper($segments[0])) {
                'SKILL.MD' => 'SKILL.md',
                'PACKAGE-MANIFEST.JSON' => 'PACKAGE-MANIFEST.json',
                default => throw new InvalidArgumentException('Only SKILL.md and PACKAGE-MANIFEST.json are allowed at package root.'),
            };
        }

        $root = strtolower($segments[0]);
        if (! in_array($root, $this->allowedRoots, true)) {
            throw new InvalidArgumentException('Skill resources must be inside an allowed resource directory.');
        }

        $segments[0] = $root;
        $normalised = implode('/', $segments);

        if (! $directory) {
            $this->assertExtensionAllowed($normalised, $root);
        }

        return $normalised;
    }

    public function duplicateKey(string $path): string
    {
        $normalised = $this->normaliseArchivePath($path, str_ends_with($path, '/'));

        return function_exists('mb_strtolower')
            ? mb_strtolower($normalised, 'UTF-8')
            : strtolower($normalised);
    }

    /**
     * @return array{path: string, size: int, sha256: string, mime_type: string|null}
     */
    public function validateContent(string $path, string $content): array
    {
        $path = $this->normalisePackagePath($path);
        $size = strlen($content);

        if ($size > $this->maxFileBytes) {
            throw new InvalidArgumentException('A skill package file exceeds the configured per-file size limit.');
        }

        $this->assertNoExecutableMagic($content);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $root = strtolower(explode('/', $path)[0]);
        $isPackageFile = ! str_contains($path, '/');

        if (($isPackageFile || in_array($root, ['scripts', 'references'], true))
            && $extension !== 'pdf'
            && (str_contains($content, "\0") || preg_match('//u', $content) !== 1)) {
            throw new InvalidArgumentException('Text skill resources must contain valid UTF-8 text.');
        }

        if (preg_match('/<\?(?:php|=)/i', $content) === 1) {
            throw new InvalidArgumentException('PHP code is not allowed in skill packages.');
        }

        if ($path === 'PACKAGE-MANIFEST.json') {
            try {
                json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('PACKAGE-MANIFEST.json must contain valid JSON.', 0, $exception);
            }
        }

        $mime = $this->detectMimeType($content);
        $this->assertMagicMatchesExtension($extension, $content);

        return [
            'path' => $path,
            'size' => $size,
            'sha256' => hash('sha256', $content),
            'mime_type' => $mime,
        ];
    }

    private function normaliseUnicode(string $path): string
    {
        if (preg_match('//u', $path) !== 1) {
            throw new InvalidArgumentException('Skill package paths must be valid UTF-8.');
        }

        if (class_exists(\Normalizer::class)) {
            $normalised = \Normalizer::normalize($path, \Normalizer::FORM_C);
            if (! is_string($normalised)) {
                throw new InvalidArgumentException('Skill package path could not be Unicode-normalised.');
            }

            return $normalised;
        }

        // Without intl, reject decomposed combining sequences rather than accepting ambiguous names.
        if (preg_match('/\p{M}/u', $path) === 1) {
            throw new InvalidArgumentException('Decomposed Unicode path names require the intl extension.');
        }

        return $path;
    }

    private function assertExtensionAllowed(string $path, string $root): void
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === '' || in_array($extension, $this->dangerousExtensions, true)) {
            throw new InvalidArgumentException('Unsupported or dangerous file extension in skill package.');
        }

        $allowed = $this->allowedExtensions[$root] ?? [];
        if ($allowed !== [] && ! in_array($extension, $allowed, true)) {
            throw new InvalidArgumentException("The .{$extension} extension is not allowed in {$root} resources.");
        }
    }

    private function assertNoExecutableMagic(string $content): void
    {
        $prefix4 = substr($content, 0, 4);
        $prefix8 = substr($content, 0, 8);
        $blocked = [
            "MZ",
            "\x7FELF",
            "\xCA\xFE\xBA\xBE",
            "\xFE\xED\xFA\xCE",
            "\xFE\xED\xFA\xCF",
            "\xCE\xFA\xED\xFE",
            "\xCF\xFA\xED\xFE",
            "PK\x03\x04",
            "Rar!",
            "\x1F\x8B",
            "7z\xBC\xAF\x27\x1C",
        ];

        foreach ($blocked as $magic) {
            if (str_starts_with($prefix8, $magic) || str_starts_with($prefix4, $magic) || str_starts_with($content, $magic)) {
                throw new InvalidArgumentException('Nested archives and executable binary files are not allowed in skill packages.');
            }
        }
    }

    private function assertMagicMatchesExtension(string $extension, string $content): void
    {
        $matches = match ($extension) {
            'png' => str_starts_with($content, "\x89PNG\r\n\x1A\n"),
            'jpg', 'jpeg' => str_starts_with($content, "\xFF\xD8\xFF"),
            'gif' => str_starts_with($content, 'GIF87a') || str_starts_with($content, 'GIF89a'),
            'webp' => substr($content, 0, 4) === 'RIFF' && substr($content, 8, 4) === 'WEBP',
            'pdf' => str_starts_with($content, '%PDF-'),
            default => true,
        };

        if (! $matches) {
            throw new InvalidArgumentException('Resource content does not match its declared file extension.');
        }
    }

    private function detectMimeType(string $content): ?string
    {
        if (! class_exists(\finfo::class)) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($content);

        return is_string($mime) ? $mime : null;
    }
}
