<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelMapping extends Model
{
    protected $table = 'channel_mappings';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'local_id',
        'channel_id_remote',
        'sync_status',
        'error_message',
        'last_sync_at',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
    ];

    // Relationships
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    // Methods
    public function resolveLocalId(): string
    {
        return $this->local_id;
    }

    public function resolveRemoteId(): string
    {
        return $this->channel_id_remote;
    }

    public function markSynced(): void
    {
        $this->update([
            'sync_status' => 'synced',
            'last_sync_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markError(string $message): void
    {
        $this->update([
            'sync_status' => 'error',
            'error_message' => $message,
        ]);
    }

    public function markSyncing(): void
    {
        $this->update([
            'sync_status' => 'syncing',
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
        return $query->where('sync_status', 'pending');
    }

    public function scopeWithErrors($query)
    {
        return $query->where('sync_status', 'error');
    }
}
