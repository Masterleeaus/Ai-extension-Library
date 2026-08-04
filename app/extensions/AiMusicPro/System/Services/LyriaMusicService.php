<?php

declare(strict_types=1);

namespace App\Extensions\AiMusicPro\System\Services;

use App\Domains\Engine\Services\GeminiService;
use App\Helpers\Classes\ApiHelper;
use Exception;
use getID3;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class LyriaMusicService
{
    /**
     * Generate music using Google Lyria models via the Gemini API.
     *
     * @param  array<UploadedFile>  $images
     *
     * @return array{file_url: string, duration: float, duration_seconds: int, lyrics: string|null}
     *
     * @throws Exception
     */
    public function generate(string $model, string $prompt, array $images = [], string $outputFormat = 'mp3'): array
    {
        ApiHelper::setGeminiKey();

        $url = sprintf('%s%s:generateContent?key=%s', GeminiService::ENDPOINT, $model, config('gemini.api_key'));

        $parts = [['text' => $prompt]];

        foreach (array_slice($images, 0, 10) as $image) {
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $image->getMimeType(),
                    'data'     => base64_encode(file_get_contents($image->getRealPath())),
                ],
            ];
        }

        $generationConfig = [
            'responseModalities' => ['AUDIO', 'TEXT'],
        ];

        if ($outputFormat === 'wav' && $model === 'lyria-3-pro-preview') {
            $generationConfig['responseMimeType'] = 'audio/wav';
        }

        $body = [
            'contents' => [
                [
                    'parts' => $parts,
                ],
            ],
            'generationConfig' => $generationConfig,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
            ->timeout(600)
            ->post($url, $body);

        if (! $response->successful()) {
            throw new Exception(__('Lyria API request failed: ') . $response->body());
        }

        $json = $response->json();

        $finishReason = $json['candidates'][0]['finishReason'] ?? null;
        if ($finishReason === 'SAFETY') {
            throw new Exception(__('Music generation was blocked by safety filters. Please modify your prompt and try again.'));
        }

        $parts = $json['candidates'][0]['content']['parts'] ?? [];

        $audioData = null;
        $lyrics = null;

        foreach ($parts as $part) {
            if (isset($part['inlineData']['data'])) {
                $audioData = base64_decode($part['inlineData']['data']);
            }

            if (isset($part['text'])) {
                $lyrics = $part['text'];
            }
        }

        if (! $audioData) {
            throw new Exception(__('Failed to generate music. No audio data received from Lyria.'));
        }

        $ext = $outputFormat === 'wav' && $model === 'lyria-3-pro-preview' ? 'wav' : 'mp3';
        $fileName = 'lyria_' . auth()->id() . '_' . time() . '.' . $ext;
        $filePath = 'ai-music-pro/' . $fileName;
        $fullPath = public_path('uploads/' . $filePath);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        file_put_contents($fullPath, $audioData);

        $getID3 = new getID3;
        $fileInfo = $getID3->analyze($fullPath);
        $durationSeconds = (int) floor($fileInfo['playtime_seconds'] ?? 0);
        $durationMinutes = $durationSeconds > 0 ? round($durationSeconds / 60, 2) : 1;

        return [
            'file_url'         => $filePath,
            'duration'         => $durationMinutes,
            'duration_seconds' => $durationSeconds,
            'lyrics'           => $lyrics,
        ];
    }
}
