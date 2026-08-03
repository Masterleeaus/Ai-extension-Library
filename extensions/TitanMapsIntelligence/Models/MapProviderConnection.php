<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Models;

final class MapProviderConnection extends CompanyScopedModel
{
    protected $table = 'maps_provider_connections';

    protected $fillable = [
        'company_id',
        'branch_id',
        'workspace_id',
        'provider',
        'credential_reference',
        'enabled',
        'priority',
        'configuration',
        'capabilities',
        'terms_version',
        'last_validated_at'
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        'configuration' => 'array',
        'capabilities' => 'array',
        'last_validated_at' => 'immutable_datetime'
        ];
    }
}
