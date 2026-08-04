<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Contracts;

use App\Extensions\SystemAIChatSkills\System\Tenancy\CompanyContext;
use App\Models\User;

interface CompanyContextResolverInterface
{
    public function resolve(User $user): CompanyContext;
}
