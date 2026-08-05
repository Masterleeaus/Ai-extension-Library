<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelOrder extends Model
{
    protected $table = 'channel_orders';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'order_id_remote',
        'local_order_id',
        'status',
        'order_data',
        'error_message',
        'synced_at',
        'pushed_at',
    ];

    protected $casts = [
        'order_data' => 'json',
        'synced_at' => 'datetime',
        'pushed_at' => 'datetime',
    ];

    // Relationships
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    // Methods
    public function sync(): bool
    {
        $this->update([
            'status' => 'syncing',
        ]);

        try {
            // Sync logic would be implemented in service
            $this->update([
                'status' => 'synced',
                'synced_at' => now(),
                'error_message' => null,
            ]);
            return true;
        } catch (\Exception $e) {
            $this->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function importDetails(array $data): void
    {
        $this->update([
            'order_data' => $data,
            'status' => 'imported',
        ]);
    }

    public function pushStatus(string $status): bool
    {
        $this->update([
            'status' => $status,
            'pushed_at' => now(),
        ]);
        return true;
    }

    public function getOrderData(): array
    {
        return $this->order_data ?? [];
    }

    public function markError(string $message): void
    {
        $this->update([
            'status' => 'error',
            'error_message' => $message,
        ]);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByChannel($query, int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeWithErrors($query)
    {
        return $query->where('status', 'error');
    }

    public function scopeSynced($query)
    {
        return $query->where('status', 'synced');
    }

    public function scopeNotPushed($query)
    {
        return $query->whereNull('pushed_at');
    }
}
