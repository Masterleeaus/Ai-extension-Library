<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotEcommerce\System\Http\Controllers\Api;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\ChatbotEcommerce\System\Models\CommerceBooking;
use App\Extensions\ChatbotEcommerce\System\Models\CommerceBookingSlot;
use App\Extensions\ChatbotEcommerce\System\Services\CommerceBookingRuntime;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class CommerceBookingApiController extends Controller
{
    public function availability(Chatbot $chatbot, CommerceBookingRuntime $bookings, Request $request): JsonResponse
    {
        $filters = $request->validate([
            'product_id' => ['sometimes', 'integer', 'min:1'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $slots = $bookings->availability((int) $chatbot->getKey(), $filters);
        return response()->json(['data' => $slots->map(fn (CommerceBookingSlot $slot) => $bookings->serializeSlot($slot))->values()]);
    }

    public function reserve(Chatbot $chatbot, string $sessionId, Request $request, CommerceBookingRuntime $bookings): JsonResponse
    {
        $data = $request->validate([
            'slot_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'customer_identity_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'customer_details' => ['sometimes', 'array'],
            'customer_details.name' => ['sometimes', 'string', 'max:191'],
            'customer_details.email' => ['sometimes', 'email', 'max:191'],
            'customer_details.phone' => ['sometimes', 'string', 'max:40'],
            'metadata' => ['sometimes', 'array'],
        ]);
        $key = trim((string) ($request->header('Idempotency-Key') ?: $request->input('idempotency_key')));
        if ($key === '' || strlen($key) > 191) {
            throw ValidationException::withMessages(['idempotency_key' => 'A valid Idempotency-Key is required.']);
        }
        $claims = $request->attributes->get('commerce_session_claims', []);
        $identityId = $claims['customer_id'] ?? null;
        $booking = $bookings->reserve(
            (int) $chatbot->getKey(),
            $sessionId,
            $key,
            (int) $data['slot_id'],
            (int) ($data['quantity'] ?? 1),
            (array) ($data['customer_details'] ?? []),
            $identityId !== null ? (int) $identityId : null,
        );
        return response()->json(['data' => $bookings->serializeBooking($booking)], 201);
    }

    public function cancel(Chatbot $chatbot, string $sessionId, CommerceBooking $booking, CommerceBookingRuntime $bookings): JsonResponse
    {
        abort_unless((int) $booking->chatbot_id === (int) $chatbot->getKey(), 404);
        return response()->json(['data' => $bookings->serializeBooking($bookings->cancel($booking, $sessionId))]);
    }

    public function sellerSlots(Chatbot $chatbot, CommerceBookingRuntime $bookings): JsonResponse
    {
        $this->assertOwner($chatbot);
        $slots = CommerceBookingSlot::query()->where('chatbot_id', $chatbot->getKey())->orderBy('starts_at')->paginate(50);
        return response()->json(['data' => $slots->getCollection()->map(fn (CommerceBookingSlot $slot) => $bookings->serializeSlot($slot))->values(), 'meta' => ['current_page' => $slots->currentPage(), 'last_page' => $slots->lastPage(), 'total' => $slots->total()]]);
    }

    public function createSlot(Chatbot $chatbot, Request $request, CommerceBookingRuntime $bookings): JsonResponse
    {
        $this->assertOwner($chatbot);
        $data = $request->validate([
            'product_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'variant_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:191'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'price_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'metadata' => ['sometimes', 'array'],
        ]);
        $data['capacity'] ??= 1;
        $slot = $bookings->createSlot((int) $chatbot->getKey(), $data);
        return response()->json(['data' => $bookings->serializeSlot($slot)], 201);
    }

    public function sellerBookings(Chatbot $chatbot, Request $request, CommerceBookingRuntime $bookings): JsonResponse
    {
        $this->assertOwner($chatbot);
        $query = CommerceBooking::query()->with('slot')->where('chatbot_id', $chatbot->getKey())->orderByDesc('id');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        $rows = $query->paginate(min(max($request->integer('per_page', 50), 1), 100));
        return response()->json(['data' => $rows->getCollection()->map(fn (CommerceBooking $booking) => $bookings->serializeBooking($booking))->values(), 'meta' => ['current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'total' => $rows->total()]]);
    }

    public function cancelAsSeller(Chatbot $chatbot, CommerceBooking $booking, CommerceBookingRuntime $bookings): JsonResponse
    {
        $this->assertOwner($chatbot);
        abort_unless((int) $booking->chatbot_id === (int) $chatbot->getKey(), 404);
        return response()->json(['data' => $bookings->serializeBooking($bookings->cancel($booking))]);
    }

    private function assertOwner(Chatbot $chatbot): void
    {
        abort_unless(Auth::id() !== null && (int) $chatbot->getAttribute('user_id') === (int) Auth::id(), 403);
    }
}
