<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Encryption\Encrypter;

class Channel extends Model
{
    use SoftDeletes;

    protected $table = 'channels';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'credentials',
        'api_token',
        'api_secret',
        'enabled',
        'settings',
        'last_sync_at',
        'authenticated_at',
        'auth_status',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'json',
        'last_sync_at' => 'datetime',
        'authenticated_at' => 'datetime',
    ];

    // Relationships
    public function mappings(): HasMany
    {
        return $this->hasMany(ChannelMapping::class, 'channel_id');
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(ChannelInventory::class, 'channel_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ChannelOrder::class, 'channel_id');
    }

    public function pricings(): HasMany
    {
        return $this->hasMany(ChannelPricing::class, 'channel_id');
    }

    // Accessor/Mutator for encrypted credentials
    public function getCredentialsAttribute(?string $value): array
    {
        if (!$value) {
            return [];
        }
        try {
            return json_decode($value, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function setCredentialsAttribute(array|string|null $value): void
    {
        if (is_array($value)) {
            $this->attributes['credentials'] = json_encode($value);
        } else {
            $this->attributes['credentials'] = $value;
        }
    }

    // Methods
    public function authenticate(): bool
    {
        $connector = $this->getConnector();
        return $connector->authenticate();
    }

    public function test(): array
    {
        $connector = $this->getConnector();
        return $connector->test();
    }

    public function sync(): array
    {
        $connector = $this->getConnector();
        return [
            'inventory' => $connector->syncInventory(),
            'orders' => $connector->syncOrders(),
            'pricing' => $connector->syncPricing(),
        ];
    }

    public function getConnector()
    {
        $connectorClass = 'App\\Domains\\WorkCore\\System\\Modules\\Channels\\Connectors\\' .
            ucfirst($this->type) . 'Connector';

        if (!class_exists($connectorClass)) {
            throw new \Exception("Connector not found: {$connectorClass}");
        }

        return new $connectorClass($this);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function isAuthenticated(): bool
    {
        return $this->auth_status === 'authenticated' && $this->authenticated_at !== null;
    }

    public function markAuthenticated(): void
    {
        $this->update([
            'auth_status' => 'authenticated',
            'authenticated_at' => now(),
        ]);
    }

    public function markAuthenticationFailed(): void
    {
        $this->update([
            'auth_status' => 'failed',
        ]);
    }
}
