<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Actions\Policies;

use App\Domains\WorkCore\System\Actions\Contracts\EntitlementResolverContract;
use App\Domains\WorkCore\System\Capabilities\CapabilityRegistry;
use App\Domains\WorkCore\System\Verticals\CompanyVerticalProfileResolver;
use Illuminate\Contracts\Container\Container;

final class VerticalAwareEntitlementResolver implements EntitlementResolverContract
{
    public function __construct(
        private readonly CapabilityRegistry $capabilities,
        private readonly CompanyVerticalProfileResolver $profiles,
        private readonly Container $container,
    ) {}

    public function allows(int $companyId, ?string $capability): bool
    {
        if ($capability === null) {
            return true;
        }
        if (! $this->capabilities->has($capability)) {
            return false;
        }
        if (! $this->profiles->resolve($companyId)->supportsCapability($capability)) {
            return false;
        }

        $resolverClass = (string) config(
            'workcore.host.entitlements.plan_resolver',
            MagicAIPlanEntitlementResolver::class,
        );
        if ($resolverClass === '' || $resolverClass === self::class) {
            return false;
        }
        $resolver = $this->container->make($resolverClass);
        return $resolver instanceof EntitlementResolverContract
            && $resolver->allows($companyId, $capability);
    }
}
