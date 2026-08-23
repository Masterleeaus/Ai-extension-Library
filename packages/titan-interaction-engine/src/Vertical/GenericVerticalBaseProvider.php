<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Vertical;

use TitanZero\Interaction\Vertical\Contracts\VerticalContextProviderInterface;
use TitanZero\Interaction\Vertical\DTO\VerticalContextLayer;

final class GenericVerticalBaseProvider implements VerticalContextProviderInterface
{
    public function layer(array $input): VerticalContextLayer
    {
        return VerticalContextLayer::fromArray([
            'id' => 'platform.generic-business',
            'kind' => 'platform_base',
            'version' => '1.0.0',
            'source' => 'titan-interaction-engine:generic-business@1.0.0',
            'values' => [
                'terminology' => [
                    'entity.customer.singular' => 'Customer',
                    'entity.customer.plural' => 'Customers',
                    'entity.work.singular' => 'Work item',
                    'entity.work.plural' => 'Work items',
                    'entity.location.singular' => 'Location',
                    'entity.location.plural' => 'Locations',
                    'entity.worker.singular' => 'Team member',
                    'entity.worker.plural' => 'Team members',
                    'entity.asset.singular' => 'Asset',
                    'entity.asset.plural' => 'Assets',
                    'action.schedule' => 'Schedule',
                    'action.complete' => 'Complete',
                ],
                'theme' => [
                    'brand.primary' => 'system-brand',
                    'brand.accent' => 'system-accent',
                    'surface.default' => 'system-surface',
                    'text.default' => 'system-text',
                    'density' => 'standard',
                    'state.success' => 'system-success',
                    'state.warning' => 'system-warning',
                    'state.danger' => 'system-danger',
                    'state.information' => 'system-information',
                    'state.offline' => 'system-offline',
                    'state.conflict' => 'system-conflict',
                    'state.permission' => 'system-permission',
                    'state.disabled' => 'system-disabled',
                ],
                'navigation' => [
                    'slots' => ['primary', 'workspace', 'context', 'assistant', 'system_status'],
                    'items' => ['home', 'today', 'work', 'customers', 'team', 'reports', 'settings'],
                ],
                'workspaces' => [
                    'default' => [
                        'label' => 'Workspace',
                        'priority' => ['today', 'work', 'customers'],
                    ],
                ],
                'capabilities' => [],
                'questions' => [],
                'forms' => [],
                'checklists' => [],
                'widgets' => [],
                'ai' => [],
                'activation' => [],
            ],
            'constraints' => [
                'forbidden_sections' => ['permissions', 'authentication', 'shell', 'raw_css'],
                'operational_state_is_authoritative' => true,
            ],
            'provenance' => [
                'source' => 'platform.generic-business',
                'source_type' => 'system_default',
                'confidence' => 1.0,
                'confirmed' => true,
                'risk' => 'low',
                'revision' => 0,
            ],
        ]);
    }
}
