<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Models;

use App\Extensions\Migration\System\Tenancy\Concerns\BelongsToMigrationCompany;
use Illuminate\Database\Eloquent\Model;

abstract class TenantMigrationModel extends Model
{
    use BelongsToMigrationCompany;

    protected $guarded = [];

    protected $casts = [
        'company_id' => 'integer',
        'user_id' => 'integer',
        'team_id' => 'integer',
    ];
}
