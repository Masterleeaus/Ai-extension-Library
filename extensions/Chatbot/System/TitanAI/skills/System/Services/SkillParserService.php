<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Services;

use App\Extensions\SystemAIChatSkills\System\Imports\SkillArchiveInspector;
use App\Extensions\SystemAIChatSkills\System\Imports\SkillImportFileSetInspector;
use App\Extensions\SystemAIChatSkills\System\Imports\SkillImportSecurityPolicy;
use App\Extensions\SystemAIChatSkills\System\Imports\SkillImportStagingArea;
use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use App\Extensions\SystemAIChatSkills\System\Skills\SkillManifestValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class SkillParserService
{
    public function __construct(
        private readonly SkillManifestValidator $manifestValidator,
        private readonly SkillArchiveInspector $archiveInspector,
        private readonly SkillImportFileSetInspector $fileSetInspector,
        private readonly SkillImportSecurityPolicy $securityPolicy,
        private readonly SkillImportStagingArea $staging,
    ) {
    }

    /**
     * Parse an uploaded .zip or .skill file.
     *
     * @return array{name: string, description: string, instructions: string, bundled_resources: array|null}
     */
    public function parseSkillFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $path = $file->getRealPath();

        if (! is_string($path) || ! is_file($path)) {
            throw new RuntimeException(__('The uploaded skill file could not be read.'));
        }

        $isZip = in_array($extension, ['zip', 'skill'], true)
            || $mime === 'application/zip'
            || $this->hasZipMagicBytes($path);

        if ($isZip) {
            $parsed = $this->parseZipFile($file);
        } else {
            $content = file_get_contents($path);
            if (! is_string($content)) {
                throw new RuntimeException(__('The uploaded skill file could not be read.'));
            }

            $inspection = $this->fileSetInspector->inspect(
                [['path' => 'SKILL.md', 'content' => $content]],
                ['source' => 'upload', 'archive' => false],
            );
            $parsed = Skill::parseSkillMd($content);
            $parsed['files'] = $inspection['files'];
            $parsed['bundled_resources'] = $inspection['bundled_resources'] ?: null;
            $parsed['import_report'] = $inspection['import_report'];
        }

        $metadata = is_array($parsed['metadata'] ?? null) ? $parsed['metadata'] : [];
        $metadata['provenance'] = array_merge(
            is_array($metadata['provenance'] ?? null) ? $metadata['provenance'] : [],
            [
                'type' => 'upload',
                'original_filename' => $file->getClientOriginalName(),
                'source_file_sha256' => hash_file('sha256', $path) ?: null,
            ],
        );
        $metadata['import_report'] = $parsed['import_report'] ?? [];
        $parsed['metadata'] = $metadata;

        return $parsed;
    }

    /**
     * Check if an uploaded file is a zip archive (by extension, MIME, or magic bytes).
     */
    public function isZipFile(UploadedFile $file): bool
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return in_array($ext, ['zip', 'skill'])
            || $file->getMimeType() === 'application/zip'
            || $this->hasZipMagicBytes($file->getRealPath());
    }

    /**
     * Validate and normalise a generated or remotely fetched skill file set.
     *
     * @param array<int, array{path: string, content: string}> $files
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    public function inspectFiles(array $files, array $report = []): array
    {
        return $this->fileSetInspector->inspect($files, $report);
    }

    /**
     * Check if a file starts with the ZIP magic bytes (PK\x03\x04).
     */
    private function hasZipMagicBytes(string $path): bool
    {
        $handle = fopen($path, 'rb');

        if (! $handle) {
            return false;
        }

        $header = fread($handle, 4);
        fclose($handle);

        return $header === "PK\x03\x04";
    }

    /**
     * Parse a .zip file, extract SKILL.md from it.
     *
     * @return array{name: string, description: string, instructions: string, bundled_resources: array|null}
     */
    private function parseZipFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! is_string($path)) {
            throw new RuntimeException(__('The uploaded skill archive could not be read.'));
        }

        $inspection = $this->archiveInspector->inspect($path);
        $skillMdContent = null;
        foreach ($inspection['files'] as $entry) {
            if (($entry['path'] ?? null) === 'SKILL.md') {
                $skillMdContent = $entry['content'];
                break;
            }
        }

        if (! is_string($skillMdContent)) {
            throw new RuntimeException(__('No SKILL.md file found in the zip archive.'));
        }

        $parsed = Skill::parseSkillMd($skillMdContent);
        $parsed['files'] = $inspection['files'];
        $parsed['bundled_resources'] = $inspection['bundled_resources'] ?: null;
        $parsed['import_report'] = $inspection['import_report'];

        if ($inspection['package_manifest'] !== null) {
            $metadata = is_array($parsed['metadata'] ?? null) ? $parsed['metadata'] : [];
            $metadata['package_manifest'] = $inspection['package_manifest'];
            $parsed['metadata'] = $metadata;
        }

        return $parsed;
    }

    /**
     * Fetch and parse a SKILL.md from a public GitHub repository.
     *
     * @return array{name: string, description: string, instructions: string, bundled_resources: array|null, source_url: string}
     */
    /**
     * Fetch and parse a skill from a GitHub URL.
     * If URL points to a folder with SKILL.md, fetches the entire skill folder.
     * If URL points to a single .md file, fetches just that file.
     *
     * @return array{name: string, description: string, instructions: string, bundled_resources: array|null, source_url: string, files?: array}
     */
    public function parseGithubUrl(string $url): array
    {
        $parsed = $this->extractGithubOwnerRepo($url);
        $filePath = $parsed['path'];

        // If path points to a specific SKILL.md inside a folder, fetch the whole skill folder
        if ($filePath !== null && strtoupper(basename($filePath)) === 'SKILL.MD') {
            $skillFolder = dirname($filePath);

            if ($skillFolder !== '.') {
                $result = $this->fetchGithubSkillFolder($url, $skillFolder);
                $result['source_url'] = $url;
                $result['metadata'] = $this->withGithubProvenance($result['metadata'] ?? [], $parsed);

                return $result;
            }
        }

        // If path points to a folder (from /tree/ URL), fetch the skill folder
        if ($filePath !== null && str_ends_with($filePath, '/SKILL.md')) {
            $skillFolder = dirname($filePath);

            if ($skillFolder !== '.') {
                $result = $this->fetchGithubSkillFolder($url, $skillFolder);
                $result['source_url'] = $url;
                $result['metadata'] = $this->withGithubProvenance($result['metadata'] ?? [], $parsed);

                return $result;
            }
        }

        // If no path, scan for SKILL.md at repo root
        if ($filePath === null) {
            $filePath = 'SKILL.md';
        }

        // Fetch single file
        $apiUrl = "https://raw.githubusercontent.com/{$parsed['owner']}/{$parsed['repo']}/{$parsed['branch']}/{$filePath}";

        $context = stream_context_create([
            'http' => [
                'header'          => "User-Agent: MagicAI\r\n",
                'timeout'         => (int) config('system-ai-chat-skills.imports.http_timeout_seconds', 15),
                'follow_location' => true,
            ],
        ]);

        $content = $this->fetchRemoteContent(
            $apiUrl,
            $context,
            (int) config('system-ai-chat-skills.imports.max_instruction_bytes', 1024 * 1024),
            __('Could not fetch SKILL.md from the repository. Make sure it exists at the specified path.'),
        );

        $inspection = $this->fileSetInspector->inspect(
            [['path' => 'SKILL.md', 'content' => $content]],
            ['source' => 'github', 'repository' => $parsed['owner'] . '/' . $parsed['repo']],
        );
        $result = Skill::parseSkillMd($content);
        $result['files'] = $inspection['files'];
        $result['bundled_resources'] = $inspection['bundled_resources'] ?: null;
        $result['import_report'] = $inspection['import_report'];
        $result['source_url'] = $url;
        $result['metadata'] = $this->withGithubProvenance($result['metadata'] ?? [], $parsed);
        $result['metadata']['import_report'] = $inspection['import_report'];

        return $result;
    }

    /**
     * Extract owner, repo, branch, and optional file path from a GitHub URL.
     *
     * @return array{owner: string, repo: string, branch: string, path: string|null}
     */
    private function extractGithubOwnerRepo(string $url): array
    {
        $parts = parse_url($url);
        $allowedHosts = config('system-ai-chat-skills.imports.github_hosts', ['github.com']);

        if (($parts['scheme'] ?? null) !== 'https' || ! in_array(strtolower((string) ($parts['host'] ?? '')), $allowedHosts, true)) {
            throw new RuntimeException(__('Only HTTPS GitHub repository URLs are allowed.'));
        }

        // Match: github.com/owner/repo/blob/branch/path/to/SKILL.md
        if (preg_match('/github\.com\/([^\/]+)\/([^\/]+)\/blob\/([^\/]+)\/(.+)/', $url, $matches)) {
            return [
                'owner'  => $matches[1],
                'repo'   => preg_replace('/\.git$/i', '', $matches[2]),
                'branch' => $matches[3],
                'path'   => $matches[4],
            ];
        }

        // Match: github.com/owner/repo/tree/branch
        if (preg_match('/github\.com\/([^\/]+)\/([^\/]+)\/tree\/([^\/\s?]+)(?:\/(.+))?/', $url, $matches)) {
            return [
                'owner'  => $matches[1],
                'repo'   => preg_replace('/\.git$/i', '', $matches[2]),
                'branch' => $matches[3],
                'path'   => isset($matches[4]) ? rtrim($matches[4], '/') . '/SKILL.md' : null,
            ];
        }

        // Match: github.com/owner/repo
        if (preg_match('/github\.com\/([^\/]+)\/([^\/\s?]+)/', $url, $matches)) {
            return [
                'owner'  => $matches[1],
                'repo'   => preg_replace('/\.git$/i', '', $matches[2]),
                'branch' => 'main',
                'path'   => null,
            ];
        }

        throw new RuntimeException(__('Invalid GitHub URL format.'));
    }

    /**
     * Scan a GitHub repo for skill folders (containing SKILL.md).
     * Returns a list of skill folders with their file counts.
     *
     * @return array<int, array{path: string, name: string, folder: string, file_count: int}>
     */
    public function scanGithubRepo(string $url): array
    {
        $tree = $this->fetchGithubTree($url);

        // Find all SKILL.md files to identify skill folders
        $skillFolders = [];
        $allPaths = [];

        foreach ($tree as $item) {
            $allPaths[] = $item['path'];

            if ($item['type'] !== 'blob') {
                continue;
            }

            if (strtoupper(basename($item['path'])) === 'SKILL.MD') {
                $dir = dirname($item['path']);
                $skillName = basename($dir);

                // Skip root-level SKILL.md or template SKILL.md
                if ($dir === '.' || $skillName === 'template') {
                    continue;
                }

                $skillFolders[$dir] = [
                    'path'       => $item['path'],
                    'name'       => $skillName,
                    'folder'     => $dir,
                    'file_count' => 0,
                ];
            }
        }

        // Count files per skill folder
        foreach ($allPaths as $filePath) {
            foreach ($skillFolders as $dir => &$skill) {
                if (str_starts_with($filePath, $dir . '/') && $filePath !== $skill['path']) {
                    $skill['file_count']++;
                }
            }
        }

        return array_values($skillFolders);
    }

    /**
     * Fetch the full GitHub tree for a repository.
     *
     * @return array<int, array{path: string, type: string}>
     */
    private function fetchGithubTree(string $url): array
    {
        $parsed = $this->extractGithubOwnerRepo($url);

        $apiUrl = "https://api.github.com/repos/{$parsed['owner']}/{$parsed['repo']}/git/trees/{$parsed['branch']}?recursive=1";

        $context = stream_context_create([
            'http' => [
                'header'          => "User-Agent: MagicAI\r\nAccept: application/vnd.github.v3+json\r\n",
                'timeout'         => (int) config('system-ai-chat-skills.imports.http_timeout_seconds', 15),
                'follow_location' => true,
            ],
        ]);

        $response = $this->fetchRemoteContent(
            $apiUrl,
            $context,
            (int) config('system-ai-chat-skills.imports.max_github_tree_bytes', 5 * 1024 * 1024),
            __('Could not access the repository. Make sure it is public and the URL is correct.'),
        );

        $data = json_decode($response, true);

        if (! isset($data['tree']) || ! is_array($data['tree'])) {
            throw new RuntimeException(__('Unexpected response from GitHub API.'));
        }

        return $data['tree'];
    }

    /**
     * Fetch and parse a skill folder from GitHub, including all bundled resources.
     *
     * @return array{name: string, description: string, instructions: string, bundled_resources: array|null, files: array}
     */
    public function fetchGithubSkillFolder(string $url, string $skillFolder): array
    {
        $github = $this->extractGithubOwnerRepo($url);
        $tree = $this->fetchGithubTree($url);
        $skillFolder = $this->securityPolicy->normaliseArchivePath(rtrim($skillFolder, '/'), true);

        $prefix = $skillFolder . '/';
        $files = [];

        // Collect all file paths in this skill folder and reject oversized remote packages
        // before downloading their contents.
        $maxEntries = (int) config('system-ai-chat-skills.imports.max_archive_entries', 250);
        $maxFileBytes = (int) config('system-ai-chat-skills.imports.max_file_bytes', 2 * 1024 * 1024);
        $maxTotalBytes = (int) config('system-ai-chat-skills.imports.max_uncompressed_bytes', 25 * 1024 * 1024);
        $filePaths = [];
        $declaredBytes = 0;

        foreach ($tree as $item) {
            if (($item['type'] ?? null) !== 'blob' || ! is_string($item['path'] ?? null) || ! str_starts_with($item['path'], $prefix)) {
                continue;
            }

            $declaredSize = is_numeric($item['size'] ?? null) ? (int) $item['size'] : null;
            if ($declaredSize !== null && $declaredSize > $maxFileBytes) {
                throw new RuntimeException(__('A GitHub skill resource exceeds the configured per-file size limit.'));
            }

            $filePaths[] = $item['path'];
            if (count($filePaths) > $maxEntries) {
                throw new RuntimeException(__('The GitHub skill folder contains too many files.'));
            }

            if ($declaredSize !== null) {
                $declaredBytes += $declaredSize;
                if ($declaredBytes > $maxTotalBytes) {
                    throw new RuntimeException(__('The GitHub skill folder exceeds the configured total byte limit.'));
                }
            }
        }

        $context = stream_context_create([
            'http' => [
                'header'          => "User-Agent: MagicAI\r\n",
                'timeout'         => (int) config('system-ai-chat-skills.imports.http_timeout_seconds', 15),
                'follow_location' => true,
            ],
        ]);

        // Fetch each file's content
        foreach ($filePaths as $filePath) {
            $rawUrl = "https://raw.githubusercontent.com/{$github['owner']}/{$github['repo']}/{$github['branch']}/{$filePath}";
            $content = $this->fetchRemoteContent(
                $rawUrl,
                $context,
                $maxFileBytes,
                __('Could not fetch :path from the repository.', ['path' => $filePath]),
            );

            // Get relative path within the skill folder
            $relativePath = Str::after($filePath, $prefix);

            $files[] = [
                'path'    => $relativePath,
                'content' => $content,
            ];
        }

        // Find SKILL.md and parse it
        $skillMdContent = null;
        foreach ($files as $file) {
            if (strtoupper(basename($file['path'])) === 'SKILL.MD' && dirname($file['path']) === '.') {
                $skillMdContent = $file['content'];

                break;
            }
        }

        // Fallback: any SKILL.md
        if ($skillMdContent === null) {
            foreach ($files as $file) {
                if (strtoupper(basename($file['path'])) === 'SKILL.MD') {
                    $skillMdContent = $file['content'];

                    break;
                }
            }
        }

        if ($skillMdContent === null) {
            throw new RuntimeException(__('No SKILL.md found in the skill folder.'));
        }

        $inspection = $this->fileSetInspector->inspect($files, [
            'source' => 'github',
            'repository' => $github['owner'] . '/' . $github['repo'],
            'folder' => $skillFolder,
        ]);
        $parsed = Skill::parseSkillMd($skillMdContent);
        $parsed['files'] = $inspection['files'];
        $parsed['bundled_resources'] = $inspection['bundled_resources'] ?: null;
        $parsed['import_report'] = $inspection['import_report'];
        $parsed['metadata'] = $this->withGithubProvenance($parsed['metadata'] ?? [], $github);
        $parsed['metadata']['import_report'] = $inspection['import_report'];

        return $parsed;
    }

    /**
     * Fetch and parse a specific file from a GitHub repo by path.
     * Used as fallback for single-file imports.
     *
     * @return array{name: string, description: string, instructions: string, bundled_resources: array|null}
     */
    public function fetchGithubFile(string $url, string $filePath): array
    {
        $parsed = $this->extractGithubOwnerRepo($url);
        $filePath = $this->securityPolicy->normaliseArchivePath($filePath);

        $rawUrl = "https://raw.githubusercontent.com/{$parsed['owner']}/{$parsed['repo']}/{$parsed['branch']}/{$filePath}";

        $context = stream_context_create([
            'http' => [
                'header'          => "User-Agent: MagicAI\r\n",
                'timeout'         => (int) config('system-ai-chat-skills.imports.http_timeout_seconds', 15),
                'follow_location' => true,
            ],
        ]);

        $content = $this->fetchRemoteContent(
            $rawUrl,
            $context,
            (int) config('system-ai-chat-skills.imports.max_instruction_bytes', 1024 * 1024),
            __('Could not fetch :path from the repository.', ['path' => $filePath]),
        );

        $inspection = $this->fileSetInspector->inspect(
            [['path' => 'SKILL.md', 'content' => $content]],
            ['source' => 'github', 'repository' => $parsed['owner'] . '/' . $parsed['repo']],
        );
        $result = Skill::parseSkillMd($content);
        $result['files'] = $inspection['files'];
        $result['bundled_resources'] = $inspection['bundled_resources'] ?: null;
        $result['import_report'] = $inspection['import_report'];
        $result['metadata'] = $this->withGithubProvenance($result['metadata'] ?? [], $parsed);
        $result['metadata']['import_report'] = $inspection['import_report'];

        return $result;
    }

    /**
     * Read a remote response through a bounded stream so an upstream server cannot
     * make the PHP worker buffer an arbitrarily large body before validation.
     *
     * @param resource $context
     */
    private function fetchRemoteContent(string $url, $context, int $maxBytes, string $errorMessage): string
    {
        if ($maxBytes < 1) {
            throw new RuntimeException(__('Remote import byte limits must be greater than zero.'));
        }

        $stream = @fopen($url, 'rb', false, $context);
        if (! is_resource($stream)) {
            throw new RuntimeException($errorMessage);
        }

        try {
            $content = stream_get_contents($stream, $maxBytes + 1);
        } finally {
            fclose($stream);
        }

        if (! is_string($content)) {
            throw new RuntimeException($errorMessage);
        }
        if (strlen($content) > $maxBytes) {
            throw new RuntimeException(__('A remote skill import exceeded the configured byte limit.'));
        }

        return $content;
    }

    /**
     * Extract and store all skill files from a zip to disk.
     *
     * @return array<string, array<int, array{name: string, path: string, size: int}>>
     */
    public function extractAndStoreZipFiles(UploadedFile $file, int|string|null $ownerScope, string $slug): array
    {
        $parsed = $this->parseSkillFile($file);

        return $this->storeSkillFiles($parsed['files'] ?? [], $ownerScope, $slug);
    }

    public function storeSkillMdFile(string $content, int|string|null $ownerScope, string $slug): void
    {
        $this->storeSkillFiles([['path' => 'SKILL.md', 'content' => $content]], $ownerScope, $slug);
    }

    /**
     * Backward-compatible atomic storage helper. New imports should use SkillImportService
     * so database persistence and filesystem promotion share one transaction boundary.
     *
     * @param array<int, array{path: string, content: string}> $files
     * @return array<string, array<int, array{name: string, path: string, size: int, sha256: string, mime_type: string|null}>>
     */
    public function storeSkillFiles(array $files, int|string|null $ownerScope, string $slug): array
    {
        $inspection = $this->fileSetInspector->inspect($files, ['source' => 'legacy-storage-helper']);
        $staged = $this->staging->stage($inspection['files']);
        $finalPath = $this->staging->finalSkillPath($ownerScope, $slug);

        try {
            $this->staging->promote($staged, $finalPath);

            return $this->staging->describeResources($finalPath);
        } finally {
            $this->staging->deleteDirectory($staged->path);
        }
    }

    /**
     * Validate the parsed skill data has required fields.
     */
    public function validateSchema(array $parsed): bool
    {
        try {
            $this->manifestValidator->validate($parsed);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Sanitize instructions to prevent prompt injection patterns.
     */
    public function sanitizeInstructions(string $instructions): string
    {
        // Strip HTML/script tags
        $instructions = strip_tags($instructions);

        // Limit length
        $instructions = Str::limit($instructions, 50000, '');

        return $instructions;
    }


    /** @param array<string, mixed> $metadata @param array{owner: string, repo: string, branch: string, path: string|null} $github */
    private function withGithubProvenance(array $metadata, array $github): array
    {
        $branch = (string) $github['branch'];
        $metadata['provenance'] = array_merge(
            is_array($metadata['provenance'] ?? null) ? $metadata['provenance'] : [],
            [
                'type' => 'github',
                'repository' => $github['owner'] . '/' . $github['repo'],
                'branch' => $branch,
                'commit_sha' => preg_match('/^[a-f0-9]{40}$/i', $branch) ? strtolower($branch) : null,
                'path' => $github['path'],
            ],
        );

        return $metadata;
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

    private function normaliseStorageOwner(int|string|null $ownerScope): string
    {
        if ($ownerScope === null) {
            return 'public';
        }

        $owner = (string) $ownerScope;
        if (! preg_match('/^(?:[1-9][0-9]*|company-[1-9][0-9]*|public)$/', $owner)) {
            throw new RuntimeException(__('Invalid skill storage owner scope.'));
        }

        return $owner;
    }

    public function isSecureFilePath(string $path): bool
    {
        try {
            $this->securityPolicy->normalisePackagePath($path);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    public function isSecureFileSize(string $content, ?int $maxBytes = null): bool
    {
        $limit = $maxBytes ?? (int) config('system-ai-chat-skills.imports.max_file_bytes', 2_097_152);

        return strlen($content) <= $limit;
    }
}
