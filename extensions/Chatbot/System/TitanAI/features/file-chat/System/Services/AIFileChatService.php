<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatFileChat\System\Services;

use App\Models\UserOpenaiChat;
use App\Services\Ai\OpenAI\FileSearchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AIFileChatService
{
    private array $files;

    private ?string $chatId;

    public function __construct(?string $pdfPaths = '', ?string $chatId = null)
    {
        $this->files = $this->filterValidFiles($pdfPaths);
        $this->chatId = $chatId;
    }

    /**
     * Ensure all provided files exist and are valid.
     */
    private function filterValidFiles(?string $pdfPaths = ''): array
    {
        if (empty($pdfPaths)) {
            return [];
        }

        $paths = array_slice(array_map('trim', explode(',', $pdfPaths)), 0, (int) config('systemaichatfilechat.max_files_per_request', 5));
        $totalBytes = 0;
        $maxTotalBytes = (int) config('systemaichatfilechat.max_total_bytes', 26214400);
        $allowedMimeTypes = (array) config('systemaichatfilechat.allowed_mime_types', []);

        return array_values(array_filter($paths, static function ($path) use (&$totalBytes, $maxTotalBytes, $allowedMimeTypes) {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $parts = parse_url($path);
                if (($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])) {
                    return false;
                }
                $ip = filter_var($parts['host'], FILTER_VALIDATE_IP) ? $parts['host'] : null;
                return $ip === null || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
            }

            // Otherwise, check if the file exists in public path
            $fullPath = realpath(public_path($path));
            $publicRoot = realpath(public_path());

            if ($fullPath === false || $publicRoot === false
                || ! str_starts_with($fullPath, $publicRoot . DIRECTORY_SEPARATOR)
                || ! is_file($fullPath)) {
                return false;
            }

            $size = (int) filesize($fullPath);
            if ($size > (int) config('systemaichatfilechat.max_file_bytes', 26214400)) {
                return false;
            }

            $totalBytes += $size;
            if ($totalBytes > $maxTotalBytes) {
                return false;
            }

            if ($allowedMimeTypes !== []) {
                $mime = (string) (mime_content_type($fullPath) ?: '');
                if (! in_array($mime, $allowedMimeTypes, true)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Validates and decides which analyzer to run.
     */
    public function validateAndAnalyzeFile(): bool
    {
        if (empty($this->files)) {

            try {
                $chat = UserOpenaiChat::query()->whereKey($this->chatId)->where('user_id', Auth::id())->firstOrFail();

                $containsPdfData = $chat->messages()
                    ->whereNotNull('pdfPath')
                    ->where('pdfPath', '!=', '')
                    ->limit(1)
                    ->exists();

                if (! $containsPdfData) {
                    $chat->update([
                        'openai_vector_id' => '',
                        'openai_file_id'   => '',
                        'reference_url'    => '',
                    ]);
                }

                if ($chat->openai_vector_id && $chat->openai_file_id && $chat->reference_url) {
                    return true;
                }
            } catch (Throwable $e) {
                Log::error('AIFileChatService no files error', [
                    'chat_id' => $this->chatId,
                    'error'   => $e->getMessage(),
                ]);

                return false;
            }

            return false;
        }

        if ((int) setting('openai_file_search', 0) === 1
            || (int) setting('chatpro_file_chat_allowed', 1) === 1) {
            return $this->storeDocForChatResponseApi();
        }

        return false;
    }

    /**
     * Store document and attach to chat record.
     */
    private function storeDocForChatResponseApi(): bool
    {
        try {
            if (empty($this->chatId)) {
                return false;
            }

            $chat = UserOpenaiChat::query()->whereKey($this->chatId)->where('user_id', Auth::id())->firstOrFail();

            $filePath = $this->files[0];

            if (! filter_var($filePath, FILTER_VALIDATE_URL)) {
                $filePath = public_path($filePath);
            }
            $fileSearchService = new FileSearchService;

            $fileId = $fileSearchService->uploadFile($filePath);
            $vectors = $fileSearchService->createVectorStore(basename($filePath), $fileId);

            if (empty($fileId) || empty($vectors?->id)) {
                return false;
            }

            $fileSearchService->waitForVectorStoreFilesReady($vectors->id);

            $chat->update([
                'openai_vector_id' => $vectors->id,
                'openai_file_id'   => $fileId,
                'reference_url'    => $this->files[0],
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('AIFileChatService error', [
                'chat_id' => $this->chatId,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function contentFingerprint(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new \InvalidArgumentException('File cannot be fingerprinted.');
        }
        return hash_file('sha256', $path);
    }
}
