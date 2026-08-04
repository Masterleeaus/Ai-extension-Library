<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Actions\Policies;

use App\Domains\WorkCore\System\Actions\Contracts\EntitlementResolverContract;
use App\Domains\WorkCore\System\Capabilities\CapabilityRegistry;
use App\Helpers\Classes\PlanHelper;

final class MagicAIPlanEntitlementResolver implements EntitlementResolverContract
{
    public function __construct(private CapabilityRegistry $capabilities) {}

    public function allows(int $companyId, ?string $capability): bool
    {
        if ($capability === null) {
            return true;
        }
        if (! $this->capabilities->has($capability)) {
            return false;
        }
        if (in_array($capability, (array) config('workcore.host.entitlements.free_capabilities', []), true)) {
            return true;
        }
        if (! class_exists(PlanHelper::class)) {
            return false;
        }
        $plan = PlanHelper::userPlan();
        if (! $plan) {
            return false;
        }
        $key = (string) config('workcore.host.entitlements.capability_plan_keys.' . $capability, $capability);
        $features = array_merge((array) $plan->plan_features, (array) $plan->plan_ai_tools);
        return (bool) ($features[$key] ?? false);
    }
}
