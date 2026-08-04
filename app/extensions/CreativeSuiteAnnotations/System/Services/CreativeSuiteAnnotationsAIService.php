<?php

declare(strict_types=1);

namespace App\Extensions\CreativeSuiteAnnotations\System\Services;

use App\Domains\Entity\Enums\EntityEnum;
use App\Helpers\Classes\ApiHelper;
use App\Services\Ai\AIImageClient;
use App\Services\Ai\OpenAI\Image\CreateImageEditService;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use RuntimeException;

class CreativeSuiteAnnotationsAIService
{
    /**
     * Dispatch an annotation edit. Always receives the full source image and
     * a full-size mask. Returns either an async handle (request_id to poll)
     * or a sync result (path to the finished full image). Either way the
     * client just replaces the image fill with the resulting URL.
     *
     * @return array{
     *     async: bool,
     *     request_id?: string,
     *     output?: string,
     * }
     */
    public static function dispatch(
        string $prompt,
        EntityEnum $entity,
        UploadedFile $image,
        ?UploadedFile $mask = null,
    ): array {
        if ($mask === null) {
            throw new RuntimeException(__('Mask is required.'));
        }

        if (self::supportsMask($entity)) {
            return self::isOpenAIMaskNative($entity)
                ? self::dispatchOpenAIMask($prompt, $entity, $image, $mask)
                : self::dispatchMultiImageMask($prompt, $entity, $image, $mask);
        }

        return self::dispatchCropAndComposite($prompt, $entity, $image, $mask);
    }

    public static function supportsMask(EntityEnum $entity): bool
    {
        $models = (array) config('creative-suite-annotations.mask_capable_models', []);

        return in_array($entity->value, $models, true);
    }

    /**
     * Wrap raw per-pin prompts with the marker scaffolding the model needs
     * to apply each instruction at the right location and clean up the
     * burned-in numbered red dots from the source image.
     *
     * Kept server-side so the prompt template isn't visible to the client.
     *
     * @param  list<string>  $pinPrompts  raw per-pin prompts in pin order
     */
    public static function buildCommentPrompt(array $pinPrompts): string
    {
        $lines = [];
        $i = 1;

        foreach ($pinPrompts as $prompt) {
            if (! is_string($prompt)) {
                continue;
            }
            $trimmed = trim($prompt);
            if ($trimmed === '') {
                continue;
            }
            $lines[] = sprintf('Marker %d: %s', $i, $trimmed);
            $i++;
        }

        return implode("\n", [
            'Edit the image according to each numbered red marker. Apply the change exactly at the location indicated by its red numbered dot.',
            ...$lines,
            'The numbered red markers are visual guides only — remove them entirely from the output. Keep the rest of the image unchanged.',
        ]);
    }

    /**
     * Wrap raw {original_text, new_text} pairs for the replace-text tool with
     * the same numbered-marker scaffolding the comment tool uses. The image
     * already has numbered red markers burned in at each pin position; this
     * prompt teaches the model to replace the existing text at each marker
     * while preserving the surrounding image (font, perspective, colour, etc.)
     * and to remove the markers themselves from the output.
     *
     * Kept server-side so the prompt template isn't visible to the client.
     *
     * @param  list<array{original_text?: string, new_text?: string}>  $textEdits
     */
    public static function buildReplaceTextPrompt(array $textEdits): string
    {
        $lines = [];
        $i = 1;

        foreach ($textEdits as $edit) {
            if (! is_array($edit)) {
                continue;
            }

            $orig = trim((string) ($edit['original_text'] ?? ''));
            $new = trim((string) ($edit['new_text'] ?? ''));

            if ($new === '') {
                continue;
            }

            $lines[] = $orig === ''
                ? sprintf('Marker %d: Add the text "%s" at this location.', $i, $new)
                : sprintf('Marker %d: Replace the existing text "%s" with "%s".', $i, $orig, $new);
            $i++;
        }

        return implode("\n", [
            'Edit the image according to each numbered red marker. At each marker, perform a localised in-place text edit.',
            ...$lines,
            'Preserve the existing font, weight, size, color, alignment, perspective, and any background, shape, or label the text sits on. Match the surrounding lighting and resolution. Do not move text out of its original position.',
            'The numbered red markers are visual guides only — remove them entirely from the output. Keep the rest of the image unchanged.',
        ]);
    }

    /**
     * Wrap a raw user prompt for the region tools (rect / oval / lasso / brush).
     * Without this scaffolding, mask-based models tend to treat the masked
     * region as "fully replace with whatever the prompt describes" — e.g. a
     * lasso around a text label combined with "change the text to FOO" ends
     * up blanking the surrounding context and reflowing the text elsewhere
     * instead of doing a localised in-place edit.
     *
     * Kept server-side so the prompt template isn't visible to the client.
     */
    public static function buildRegionPrompt(string $tool, string $prompt): string
    {
        $trimmed = trim($prompt);
        if ($trimmed === '') {
            return '';
        }

        $shapeWord = match ($tool) {
            'rect'  => 'rectangular',
            'oval'  => 'oval',
            'lasso' => 'lassoed',
            'brush' => 'brushed',
            default => 'selected',
        };

        return implode("\n", [
            "Edit only the {$shapeWord} region indicated by the mask. The mask defines the exact area to modify — outside that area the output must be pixel-identical to the source: same background, composition, lighting, subjects, framing, and any text or objects.",
            "Inside the masked region, perform a localised in-place edit of the existing content. Preserve every element the instruction does not address (background, frame, surrounding text, faces, objects, textures, colors). Match the surrounding image's style, perspective, lighting, color palette, and resolution so the edit blends seamlessly. Do not blank, replace, or reflow content out of the masked region. If the instruction targets text, edit the existing text in place while keeping its font, size, weight, color, alignment, and the surrounding label or shape unchanged.",
            'Instruction: ' . $trimmed,
        ]);
    }

    private static function isOpenAIMaskNative(EntityEnum $entity): bool
    {
        return str_starts_with($entity->value, 'gpt-image') || $entity->value === 'dall-e-2';
    }

    /**
     * OpenAI's images/edits endpoint accepts an alpha mask and edits only
     * the transparent regions. For gpt-image-* models we stream the response
     * (request `stream: true`, parse the SSE event stream) — slow models like
     * gpt-image-2 routinely exceed OpenAI's synchronous edge timeout
     * (~100-120s) and otherwise fail with a cURL 56 SSL_read EOF.
     * dall-e-2 stays on the SDK's sync path; it's fast and doesn't support
     * streaming.
     */
    private static function dispatchOpenAIMask(
        string $prompt,
        EntityEnum $entity,
        UploadedFile $image,
        UploadedFile $mask,
    ): array {
        if (str_starts_with($entity->value, 'gpt-image')) {
            $binary = self::streamOpenAIEdit($prompt, $entity, $image, $mask);

            return [
                'async'  => false,
                'output' => self::storeBinary($binary),
            ];
        }

        ApiHelper::setOpenAiKey();

        $imageUrl = self::publishImage($image);
        $maskUrl = self::publishImage($mask);

        $service = app(CreateImageEditService::class)
            ->setImages([$imageUrl])
            ->setMask($maskUrl)
            ->setModel($entity->value)
            ->setPrompt($prompt);

        $base64 = $service->generateForAi();

        if (! $base64) {
            throw new RuntimeException(__('OpenAI returned no image.'));
        }

        return [
            'async'  => false,
            'output' => self::storeBinary(base64_decode($base64)),
        ];
    }

    /**
     * Stream the edit via SSE. The streamed response keeps the TLS
     * connection alive past OpenAI's sync gateway cap and delivers the
     * final image as a base64 payload in an `image_edit.completed` event.
     * Earlier `image_edit.partial_image` events ship lower-fidelity drafts;
     * we use them only as a liveness signal and keep the latest b64 we've
     * seen as a fallback in case the completed event arrives without one.
     */
    private static function streamOpenAIEdit(
        string $prompt,
        EntityEnum $entity,
        UploadedFile $image,
        UploadedFile $mask,
    ): string {
        // gpt-image-2 commonly streams for 1–3 min; the default PHP execution
        // limit kills the worker mid-stream and leaves the UserOpenai row stuck.
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '-1');

        $apiKey = ApiHelper::setOpenAiKey();

        $multipart = [
            ['name' => 'model',          'contents' => $entity->value],
            ['name' => 'prompt',         'contents' => $prompt],
            ['name' => 'n',              'contents' => '1'],
            ['name' => 'size',           'contents' => 'auto'],
            ['name' => 'quality',        'contents' => 'auto'],
            ['name' => 'stream',         'contents' => 'true'],
            ['name' => 'partial_images', 'contents' => '2'],
            [
                'name'     => 'image',
                'contents' => fopen($image->getRealPath(), 'r'),
                'filename' => 'image.png',
            ],
            [
                'name'     => 'mask',
                'contents' => fopen($mask->getRealPath(), 'r'),
                'filename' => 'mask.png',
            ],
        ];

        $client = new Client;

        $response = $client->post('https://api.openai.com/v1/images/edits', [
            'multipart'       => $multipart,
            'stream'          => true,
            'timeout'         => 600,
            'connect_timeout' => 30,
            'headers'         => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept'        => 'text/event-stream',
            ],
            'http_errors'     => false,
            // Long-running PHP-FPM workers can keep a half-closed cURL
            // connection in their pool and fail the next streaming request
            // with `Connection refused`. Force a fresh TCP connection per
            // submission and don't add this one to the pool.
            'curl'            => [
                CURLOPT_FRESH_CONNECT => true,
                CURLOPT_FORBID_REUSE  => true,
            ],
        ]);

        if ($response->getStatusCode() >= 400) {
            $body = (string) $response->getBody();
            $message = data_get(json_decode($body, true), 'error.message') ?: $body;

            throw new RuntimeException(__('OpenAI rejected the edit: :msg', ['msg' => $message]));
        }

        return self::readSseFinalImage($response->getBody());
    }

    /**
     * Drain the SSE stream and return the binary of the final image. Any
     * intermediate b64 frames are remembered as a fallback in case the
     * stream closes without a clean `*.completed` event.
     */
    private static function readSseFinalImage($stream): string
    {
        $buffer = '';
        $latestB64 = null;
        $idleTimeoutSeconds = 180;
        $lastDataAt = microtime(true);

        while (! $stream->eof()) {
            $chunk = $stream->read(16384);
            if ($chunk === '') {
                if (microtime(true) - $lastDataAt > $idleTimeoutSeconds) {
                    throw new RuntimeException(__('OpenAI stream went silent for too long.'));
                }
                usleep(100000);

                continue;
            }
            $lastDataAt = microtime(true);
            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $rawEvent = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);

                $eventName = null;
                $dataLines = [];
                foreach (explode("\n", $rawEvent) as $line) {
                    if (str_starts_with($line, 'event:')) {
                        $eventName = trim(substr($line, 6));
                    } elseif (str_starts_with($line, 'data:')) {
                        $dataLines[] = ltrim(substr($line, 5));
                    }
                }

                if ($dataLines === []) {
                    continue;
                }

                $data = implode("\n", $dataLines);

                if ($data === '[DONE]') {
                    break 2;
                }

                $payload = json_decode($data, true);
                if (! is_array($payload)) {
                    continue;
                }

                if (isset($payload['error'])) {
                    throw new RuntimeException($payload['error']['message'] ?? 'OpenAI streaming error.');
                }

                if (isset($payload['b64_json']) && is_string($payload['b64_json']) && $payload['b64_json'] !== '') {
                    $latestB64 = $payload['b64_json'];
                }

                $type = (string) ($payload['type'] ?? $eventName ?? '');
                if ($type !== '' && str_ends_with($type, '.completed') && $latestB64 !== null) {
                    return base64_decode($latestB64);
                }
            }
        }

        if ($latestB64 !== null) {
            return base64_decode($latestB64);
        }

        throw new RuntimeException(__('OpenAI streaming returned no image.'));
    }

    /**
     * Mask path for providers that don't have a dedicated `mask` API field
     * but DO accept multi-image input — currently the Nano Banana / Gemini
     * Flash Image family on FalAI. The technique:
     *
     *   image_urls = [source_image, bw_mask]
     *   prompt = "<wrap that names the second image as a mask> + user prompt"
     *
     * The model sees two images; the prompt teaches it to treat the second
     * as a binary mask. Returns a full edited image, no compositing needed.
     */
    private static function dispatchMultiImageMask(
        string $prompt,
        EntityEnum $entity,
        UploadedFile $image,
        UploadedFile $mask,
    ): array {
        $imageUrl = self::publishImage($image);

        $bwMaskPath = self::alphaToBlackWhiteMask($mask->getRealPath());
        $bwMaskUpload = new UploadedFile($bwMaskPath, basename($bwMaskPath), 'image/png', null, true);

        try {
            $bwMaskUrl = self::publishImage($bwMaskUpload);

            $wrappedPrompt = self::buildMultiImageMaskPrompt($prompt);

            $result = AIImageClient::generate([
                'model'           => $entity->value,
                'prompt'          => $wrappedPrompt,
                'image_reference' => [$imageUrl, $bwMaskUrl],
            ]);

            if (isset($result['request_id'])) {
                return [
                    'async'      => true,
                    'request_id' => (string) $result['request_id'],
                ];
            }

            if (! isset($result[0])) {
                throw new RuntimeException(__('AI provider returned no image.'));
            }

            return [
                'async'  => false,
                'output' => self::storeBinary($result[0]),
            ];
        } finally {
            @unlink($bwMaskPath);
        }
    }

    private static function buildMultiImageMaskPrompt(string $userPrompt): string
    {
        return implode(' ', [
            'You are given two images:',
            '(1) the SOURCE image to edit, and',
            '(2) a black-and-white MASK image of the same dimensions as the source.',
            'WHITE pixels in the mask mark the only region that should change.',
            'BLACK pixels in the mask must remain pixel-identical to the source — do not modify them.',
            'Edit only the white-masked region of the source according to this instruction:',
            $userPrompt,
            'Output the full edited image at the same dimensions as the source. Do not include the mask, any borders, watermarks, or extra markings in the output.',
        ]);
    }

    /**
     * Convert an OpenAI-style alpha mask (source image with alpha=0 in the
     * regions to edit) into a plain black-and-white mask suitable for
     * prompting Gemini-style models. White = editable, black = preserve.
     */
    private static function alphaToBlackWhiteMask(string $alphaMaskPath): string
    {
        $src = @imagecreatefrompng($alphaMaskPath);
        if (! $src) {
            throw new RuntimeException(__('Failed to read mask.'));
        }

        imagealphablending($src, false);
        imagesavealpha($src, true);

        $w = imagesx($src);
        $h = imagesy($src);

        $out = imagecreatetruecolor($w, $h);
        $black = imagecolorallocate($out, 0, 0, 0);
        $white = imagecolorallocate($out, 255, 255, 255);
        imagefill($out, 0, 0, $black);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                // High alpha bits: 0 = opaque, 127 = fully transparent.
                $alpha = (imagecolorat($src, $x, $y) & 0x7F000000) >> 24;
                if ($alpha >= 100) {
                    imagesetpixel($out, $x, $y, $white);
                }
            }
        }

        $temp = self::tempPath('mask-bw', 'png');
        imagepng($out, $temp);
        imagedestroy($src);
        imagedestroy($out);

        return $temp;
    }

    /**
     * Fallback for providers that don't accept a mask (FalAI, xAI, etc).
     *
     * - Read the mask, find the bounding box of fully-transparent pixels.
     * - Crop that region from the source image.
     * - Send the crop through AIImageClient (Flux Kontext / Nano Banana etc).
     * - On sync success, composite the edited crop back into the full image
     *   and return the full image URL.
     * - On async, return the request_id and let the controller's status
     *   endpoint composite once the provider finishes.
     */
    private static function dispatchCropAndComposite(
        string $prompt,
        EntityEnum $entity,
        UploadedFile $image,
        UploadedFile $mask,
    ): array {
        $region = self::computeMaskBounds($mask->getRealPath());

        if ($region === null) {
            throw new RuntimeException(__('Annotation region is empty.'));
        }

        $manager = ImageManager::gd();
        $source = $manager->read($image->getRealPath());
        $crop = (clone $source)->crop($region['w'], $region['h'], $region['x'], $region['y']);

        $cropPath = self::tempPath('crop', 'png');
        $crop->toPng()->save($cropPath);
        $cropUpload = new UploadedFile($cropPath, basename($cropPath), 'image/png', null, true);

        $editedPath = null;

        try {
            $cropUrl = self::publishImage($cropUpload);

            $result = AIImageClient::generate([
                'model'           => $entity->value,
                'prompt'          => $prompt,
                'image_src'       => [$cropUpload],
                'image_reference' => $cropUrl,
            ]);

            if (isset($result['request_id'])) {
                return [
                    'async'      => true,
                    'request_id' => (string) $result['request_id'],
                    'pending'    => [
                        'mode'        => 'crop_composite',
                        'image_path'  => $image->getRealPath(),
                        'crop_origin' => $region,
                    ],
                ];
            }

            if (! isset($result[0])) {
                throw new RuntimeException(__('AI provider returned no image.'));
            }

            $editedPath = self::tempPath('edit', 'png');
            file_put_contents($editedPath, $result[0]);

            $compositedPath = self::compositeBackToSource($source, $editedPath, $region);

            return [
                'async'  => false,
                'output' => $compositedPath,
            ];
        } finally {
            @unlink($cropPath);
            if ($editedPath !== null) {
                @unlink($editedPath);
            }
        }
    }

    /**
     * Composite an edited crop back onto a full source image. Returns the
     * stored URL path of the composited result.
     */
    public static function compositeBackToSource($sourceOrPath, string $editedPath, array $region): string
    {
        $manager = ImageManager::gd();
        $source = is_string($sourceOrPath) ? $manager->read($sourceOrPath) : clone $sourceOrPath;
        $edited = $manager->read($editedPath)->resize($region['w'], $region['h']);

        $source->place($edited, 'top-left', $region['x'], $region['y']);

        $fileName = Str::uuid() . '.png';
        $relative = 'creative-suite-annotations/' . $fileName;
        $absolute = public_path('uploads/' . $relative);

        if (! is_dir(dirname($absolute))) {
            @mkdir(dirname($absolute), 0775, true);
        }

        $source->toPng()->save($absolute);

        return '/uploads/' . $relative;
    }

    /**
     * Find the bounding box of fully-transparent pixels in a PNG mask.
     * Returns ['x'=>, 'y'=>, 'w'=>, 'h'=>] or null when no mask region.
     */
    public static function computeMaskBounds(string $maskPath): ?array
    {
        $img = @imagecreatefrompng($maskPath);
        if (! $img) {
            return null;
        }

        imagealphablending($img, false);
        imagesavealpha($img, true);

        $w = imagesx($img);
        $h = imagesy($img);

        $minX = $w;
        $minY = $h;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($img, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24; // 0 = opaque, 127 = fully transparent
                if ($alpha === 127) {
                    if ($x < $minX) {
                        $minX = $x;
                    }
                    if ($y < $minY) {
                        $minY = $y;
                    }
                    if ($x > $maxX) {
                        $maxX = $x;
                    }
                    if ($y > $maxY) {
                        $maxY = $y;
                    }
                }
            }
        }

        imagedestroy($img);

        if ($maxX < 0 || $maxY < 0) {
            return null;
        }

        return [
            'x' => $minX,
            'y' => $minY,
            'w' => max(1, $maxX - $minX + 1),
            'h' => max(1, $maxY - $minY + 1),
        ];
    }

    private static function publishImage(UploadedFile $image): string
    {
        $relative = $image->store('creative-suite-annotations/inputs', 'public');

        return url('uploads/' . $relative);
    }

    private static function storeBinary(string $binary): string
    {
        $fileName = Str::uuid() . '.png';
        $path = 'creative-suite-annotations/' . $fileName;

        Storage::disk('uploads')->put($path, $binary);

        return '/uploads/' . $path;
    }

    private static function tempPath(string $prefix, string $extension): string
    {
        return tempnam(sys_get_temp_dir(), $prefix . '-') . '.' . $extension;
    }
}
