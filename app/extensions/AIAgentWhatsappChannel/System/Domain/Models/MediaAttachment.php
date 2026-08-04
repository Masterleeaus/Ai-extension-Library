<?php

declare(strict_types=1);

namespace App\Extensions\AIAgentWhatsappChannel\System\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class MediaAttachment extends Model
{
    protected $fillable = [
        'tenant_id',
        'channel_id',
        'provider_media_id',
        'source_identifier',
        'filename',
        'mime_type',
        'detected_mime_type',
        'file_hash',
        'file_size',
        'byte_count',
        'storage_path',
        'status', // pending, verified, quarantined, malicious, deleted
        'validation_errors',
        'scan_result',
        'correlation_id',
        'retention_until',
        'downloaded_at',
        'verified_at',
        'deleted_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'channel_id' => 'integer',
        'file_size' => 'integer',
        'byte_count' => 'integer',
        'validation_errors' => 'json',
        'scan_result' => 'json',
        'downloaded_at' => 'datetime',
        'verified_at' => 'datetime',
        'deleted_at' => 'datetime',
        'retention_until' => 'datetime',
    };

    public function getAttributeChannel()
    {
        return $this->belongsTo(\App\Extensions\AIAgent\System\Models\AIAgentChannel::class, 'channel_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isQuarantined(): bool
    {
        return $this->status === 'quarantined';
    }

    public function isMalicious(): bool
    {
        return $this->status === 'malicious';
    }
}
