<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Automotive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartsInventory extends Model
{
    protected $table = 'automotive_parts_inventory';

    protected $fillable = [
        'company_id',
        'part_number',
        'part_name',
        'supplier_id',
        'quantity_on_hand',
        'reorder_level',
        'unit_cost',
        'markup_percentage',
        'compatibility',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'reorder_level' => 'integer',
        'unit_cost' => 'float',
        'markup_percentage' => 'float',
        'compatibility' => 'json',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }
}
