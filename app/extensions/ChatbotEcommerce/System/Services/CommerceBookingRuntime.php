<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotEcommerce\System\Services;

use App\Extensions\ChatbotEcommerce\System\Models\CommerceBooking;
use App\Extensions\ChatbotEcommerce\System\Models\CommerceBookingSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CommerceBookingRuntime
{
    public function createSlot(int $chatbotId, array $data): CommerceBookingSlot
    {
        $startsAt = now()->parse($data['starts_at']);
        $endsAt = now()->parse($data['ends_at']);
        if ($startsAt->isPast() || $endsAt->lessThanOrEqualTo($startsAt)) {
            throw ValidationException::withMessages(['starts_at' => 'A booking slot must start in the future and end after it starts.']);
        }

        return CommerceBookingSlot::query()->create([
            'uuid' => (string) Str::uuid(),
            'chatbot_id' => $chatbotId,
            'product_id' => $data['product_id'] ?? null,
            'variant_id' => $data['variant_id'] ?? null,
            'title' => trim((string) $data['title']),
            'description' => $data['description'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'timezone' => $data['timezone'] ?? 'UTC',
            'capacity' => (int) $data['capacity'],
            'reserved_capacity' => 0,
            'price_amount' => $data['price_amount'] ?? null,
            'currency' => strtoupper((string) ($data['currency'] ?? 'AUD')),
            'status' => 'open',
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    public function availability(int $chatbotId, array $filters = [])
    {
        return CommerceBookingSlot::query()
            ->where('chatbot_id', $chatbotId)
            ->where('status', 'open')
            ->where('starts_at', '>', now())
            ->whereColumn('reserved_capacity', '<', 'capacity')
            ->when(isset($filters['product_id']), fn ($query) => $query->where('product_id', (int) $filters['product_id']))
            ->when(isset($filters['from']), fn ($query) => $query->where('starts_at', '>=', $filters['from']))
            ->when(isset($filters['to']), fn ($query) => $query->where('starts_at', '<=', $filters['to']))
            ->orderBy('starts_at')
            ->limit(min(max((int) ($filters['limit'] ?? 50), 1), 100))
            ->get();
    }

    public function reserve(int $chatbotId, string $sessionId, string $idempotencyKey, int $slotId, int $quantity, array $customerDetails = [], ?int $customerIdentityId = null): CommerceBooking
    {
        return DB::transaction(function () use ($chatbotId, $sessionId, $idempotencyKey, $slotId, $quantity, $customerDetails, $customerIdentityId): CommerceBooking {
            $existing = CommerceBooking::query()->where('chatbot_id', $chatbotId)->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                if ((int) $existing->slot_id !== $slotId || (int) $existing->quantity !== $quantity || (string) $existing->session_id !== $sessionId) {
                    throw ValidationException::withMessages(['idempotency_key' => 'This idempotency key has already been used for another booking.']);
                }

                return $existing->load('slot');
            }

            $slot = CommerceBookingSlot::query()->where('chatbot_id', $chatbotId)->whereKey($slotId)->lockForUpdate()->firstOrFail();
            if ($slot->status !== 'open' || $slot->starts_at->isPast() || $slot->availableCapacity() < $quantity) {
                throw ValidationException::withMessages(['slot_id' => 'This booking slot is no longer available for the requested capacity.']);
            }

            $slot->increment('reserved_capacity', $quantity);
            $slot->refresh();
            if ((int) $slot->reserved_capacity >= (int) $slot->capacity) {
                $slot->forceFill(['status' => 'full'])->save();
            }

            return CommerceBooking::query()->create([
                'uuid' => (string) Str::uuid(),
                'chatbot_id' => $chatbotId,
                'slot_id' => $slotId,
                'customer_identity_id' => $customerIdentityId,
                'session_id' => $sessionId,
                'quantity' => $quantity,
                'status' => 'confirmed',
                'idempotency_key' => $idempotencyKey,
                'customer_details' => $customerDetails,
            ])->load('slot');
        }, 3);
    }

    public function cancel(CommerceBooking $booking, ?string $sessionId = null): CommerceBooking
    {
        return DB::transaction(function () use ($booking, $sessionId): CommerceBooking {
            $locked = CommerceBooking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
            if ($sessionId !== null && ! hash_equals((string) $locked->session_id, $sessionId)) {
                abort(404);
            }
            if ($locked->status === 'cancelled') {
                return $locked->load('slot');
            }
            if ($locked->status !== 'confirmed') {
                throw ValidationException::withMessages(['booking' => 'Only confirmed bookings can be cancelled.']);
            }

            $slot = CommerceBookingSlot::query()->whereKey($locked->slot_id)->where('chatbot_id', $locked->chatbot_id)->lockForUpdate()->firstOrFail();
            $slot->reserved_capacity = max(0, (int) $slot->reserved_capacity - (int) $locked->quantity);
            if ($slot->status === 'full' && $slot->starts_at->isFuture()) {
                $slot->status = 'open';
            }
            $slot->save();
            $locked->forceFill(['status' => 'cancelled'])->save();

            return $locked->load('slot');
        }, 3);
    }

    public function serializeSlot(CommerceBookingSlot $slot): array
    {
        return [
            'uuid' => $slot->uuid,
            'product_id' => $slot->product_id,
            'variant_id' => $slot->variant_id,
            'title' => $slot->title,
            'description' => $slot->description,
            'starts_at' => $slot->starts_at?->toIso8601String(),
            'ends_at' => $slot->ends_at?->toIso8601String(),
            'timezone' => $slot->timezone,
            'capacity' => (int) $slot->capacity,
            'available_capacity' => $slot->availableCapacity(),
            'price_amount' => $slot->price_amount === null ? null : (int) $slot->price_amount,
            'currency' => $slot->currency,
            'status' => $slot->status,
        ];
    }

    public function serializeBooking(CommerceBooking $booking): array
    {
        return [
            'uuid' => $booking->uuid,
            'slot' => $booking->slot ? $this->serializeSlot($booking->slot) : null,
            'quantity' => (int) $booking->quantity,
            'status' => $booking->status,
            'created_at' => $booking->created_at?->toIso8601String(),
        ];
    }
}
