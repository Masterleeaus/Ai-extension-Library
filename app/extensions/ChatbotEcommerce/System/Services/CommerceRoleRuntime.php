<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotEcommerce\System\Services;

use App\Extensions\ChatbotEcommerce\System\Support\CommerceRole;
use App\Extensions\ChatbotEcommerce\System\Support\CommerceRoleRouter;
use Illuminate\Validation\ValidationException;

final class CommerceRoleRuntime
{
    /** @return array<int,array<string,mixed>> */
    public function definitions(): array
    {
        return [
            [
                'role' => CommerceRole::SHOPPING_ASSISTANT,
                'audience' => 'customer',
                'purpose' => 'Sell and support this seller’s own catalogue across the storefront and customer-facing channels.',
                'authority' => ['inform', 'prepare', 'execute_after_customer_approval'],
            ],
            [
                'role' => CommerceRole::SELLER_STEWARD,
                'audience' => 'authenticated_seller',
                'purpose' => 'Operate this seller’s catalogue, connected marketplace listings, inventory, orders, fulfilment and sales workflow.',
                'authority' => ['inform', 'prepare', 'execute_after_seller_approval'],
            ],
            [
                'role' => CommerceRole::CUSTOMER_COMMUNICATIONS,
                'audience' => 'customer_and_authenticated_seller_agents',
                'purpose' => 'Follow up on this seller’s enquiries and sales, provide transaction-grounded support, request feedback and escalate sensitive cases.',
                'authority' => ['answer_automatically', 'prepare_automatically', 'execute_within_limits', 'require_approval', 'human_only'],
            ],
        ];
    }

    /** @param array<string,mixed> $context */
    public function resolve(array $context): string
    {
        return CommerceRoleRouter::resolve($context);
    }

    /** @param array<string,mixed> $actor */
    public function assertAllowed(string $role, array $actor): void
    {
        if (! CommerceRole::valid($role)) {
            throw ValidationException::withMessages(['role' => 'The commerce role is invalid.']);
        }

        $resolved = $this->resolve(array_merge($actor, ['requested_role' => $role]));
        if ($resolved !== $role) {
            throw ValidationException::withMessages(['role' => 'The actor is not permitted to use this commerce role.']);
        }
    }
}
