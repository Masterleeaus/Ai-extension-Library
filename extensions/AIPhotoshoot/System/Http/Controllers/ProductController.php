<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers;

use App\Extensions\AIPhotoshoot\System\Models\AIPhotoshootProduct;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Load user's uploaded products
     */
    public function load(Request $request): JsonResponse
    {
        $products = AIPhotoshootProduct::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($item) => [
                'id'         => $item->id,
                'name'       => $item->name,
                'image_url'  => $item->image_url,
                'thumbnail'  => ThumbImage($item->image_url),
                'exist_type' => $item->exist_type,
                'status'     => $item->status ?? 'completed',
                'created_at' => $item->created_at,
            ]);

        return response()->json([
            'success'  => true,
            'products' => $products,
        ]);
    }

    /**
     * Upload a new product
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'product_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:' . AIPhotoshootImageModelRegistry::getMaxUploadSizeKb(),
            'name'          => 'nullable|string|max:255',
        ]);

        try {
            $userId = Auth::id();
            $file = $request->file('product_image');
            $url = processSecureFileUpload($file, 'media/images/u-' . $userId);

            $product = AIPhotoshootProduct::create([
                'user_id'    => $userId,
                'name'       => $request->input('name') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'image_url'  => $url,
                'exist_type' => 'uploaded',
                'status'     => 'completed',
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Product uploaded successfully'),
                'product' => [
                    'id'         => $product->id,
                    'name'       => $product->name,
                    'image_url'  => $product->image_url,
                    'thumbnail'  => ThumbImage($product->image_url),
                    'exist_type' => $product->exist_type,
                    'status'     => $product->status,
                    'created_at' => $product->created_at,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('AIPhotoshoot product upload failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => __('Failed to upload product: ' . $e->getMessage()),
            ], 500);
        }
    }

    /**
     * Delete a product
     */
    public function delete(string $productId): JsonResponse
    {
        try {
            $product = AIPhotoshootProduct::where('id', $productId)
                ->where('user_id', Auth::id())
                ->first();

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => __('Product not found or unauthorized'),
                ], 404);
            }

            if ($product->image_url) {
                $relativePath = Str::after($product->image_url, '/uploads/');
                Storage::disk('public')->delete($relativePath);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => __('Product deleted successfully'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete product: ' . $e->getMessage()),
            ], 500);
        }
    }
}
