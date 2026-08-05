<?php

declare(strict_types=1);

namespace App\Extensions\CreativeSuite\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Security\SvgSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ImageUploadController extends Controller
{
    public function __invoke(Request $request, SvgSanitizer $sanitizer)
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,svg', 'max:8192'],
        ]);

        /** @var UploadedFile $image */
        $image = $request->file('image');

        if ($this->isSvg($image)) {
            $path = $this->storeSanitizedSvg($image, $sanitizer);
        } else {
            $path = $image->store('', [
                'disk' => 'uploads',
            ]);
        }

        return response()->json([
            'data' => [
                'path' => 'uploads/' . $path,
                'url'  => Storage::disk('uploads')->url($path),
            ],
        ]);
    }

    private function isSvg(UploadedFile $image): bool
    {
        return strtolower((string) $image->getMimeType()) === 'image/svg+xml'
            || strtolower($image->getClientOriginalExtension()) === 'svg';
    }

    private function storeSanitizedSvg(UploadedFile $image, SvgSanitizer $sanitizer): string
    {
        $realPath = $image->getRealPath();
        $source = is_string($realPath) ? file_get_contents($realPath) : false;

        if (! is_string($source)) {
            throw ValidationException::withMessages([
                'image' => __('The SVG file could not be read.'),
            ]);
        }

        try {
            $sanitized = $sanitizer->sanitize($source);
        } catch (RuntimeException) {
            throw ValidationException::withMessages([
                'image' => __('The SVG file is invalid or contains unsafe content.'),
            ]);
        }

        $path = Str::uuid() . '.svg';

        if (! Storage::disk('uploads')->put($path, $sanitized)) {
            throw new RuntimeException('Unable to store the sanitized SVG.');
        }

        return $path;
    }
}
