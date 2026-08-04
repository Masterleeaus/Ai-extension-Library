<?php

declare(strict_types=1);

$action = static fn (
    string $tool,
    string $target,
    string $description,
    string $risk,
    bool $confirmation,
    string $permission,
    array $required = [],
): array => [
    'tool' => [
        'name' => $tool,
        'description' => $description,
        'kind' => 'action',
        'target' => $target,
        'risk' => $risk,
        'requires_confirmation' => $confirmation,
        'channels' => ['internal', 'chatbot-http', 'pwa'],
        'agents' => ['*'],
        'input_schema' => [
            'type' => 'object',
            'properties' => array_fill_keys($required, ['type' => ['string', 'integer', 'number']]),
            'required' => $required,
            'additionalProperties' => true,
        ],
    ],
    'action' => [
        'risk' => $risk,
        'requires_confirmation' => $confirmation,
        'capability' => 'workcore.cleaning.'.$target,
        'permission' => $permission,
    ],
];

$definitions = [
    'invoicing.create_invoice' => $action('cleaning.invoices.create', 'invoicing.create_invoice', 'Create one invoice from authorised WorkCore records.', 'medium', false, 'invoices.create', ['customer_id']),
    'invoicing.send_invoice' => $action('cleaning.invoices.send', 'invoicing.send_invoice', 'Send one existing invoice through an approved channel.', 'high', true, 'invoices.send', ['invoice_id']),
    'invoicing.apply_payment' => $action('cleaning.payments.record', 'invoicing.apply_payment', 'Record one verified payment against an invoice.', 'critical', true, 'payments.create', ['invoice_id', 'amount']),
    'invoicing.create_quote' => $action('cleaning.quotes.create', 'invoicing.create_quote', 'Create one governed customer quote.', 'medium', false, 'quotes.create', ['customer_id']),
    'jobs.create_job_order' => $action('cleaning.jobs.book', 'jobs.create_job_order', 'Create one cleaning job or booking.', 'medium', false, 'jobs.create', ['customer_id']),
    'scheduling.reschedule_appointment' => $action('cleaning.jobs.reschedule', 'scheduling.reschedule_appointment', 'Reschedule one existing job.', 'medium', false, 'schedules.update', ['job_id', 'scheduled_at']),
    'jobs.cancel_job' => $action('cleaning.jobs.cancel', 'jobs.cancel_job', 'Cancel one existing job.', 'high', true, 'jobs.cancel', ['job_id']),
    'jobs.assign_to_technician' => $action('cleaning.dispatch.assign_cleaner', 'jobs.assign_to_technician', 'Assign one authorised cleaner to a job.', 'medium', false, 'jobs.assign', ['job_id', 'worker_id']),
    'field_ops.optimize_route' => $action('cleaning.routes.optimise', 'field_ops.optimize_route', 'Optimise a route for an authorised set of jobs.', 'low', false, 'routes.update', ['job_ids']),
    'jobs.complete_job' => $action('cleaning.jobs.complete', 'jobs.complete_job', 'Complete one job after required evidence is present.', 'medium', false, 'jobs.complete', ['job_id']),
    'crm.create_contact' => $action('cleaning.customers.create', 'crm.create_contact', 'Create one customer record.', 'low', false, 'customers.create', ['name']),
    'crm.update_contact' => $action('cleaning.customers.update', 'crm.update_contact', 'Update one customer record.', 'low', false, 'customers.update', ['customer_id']),
    'inventory.adjust_stock' => $action('cleaning.inventory.adjust', 'inventory.adjust_stock', 'Apply one stock adjustment.', 'medium', false, 'inventory.update', ['inventory_item_id', 'quantity_delta']),
    'field_ops.record_incident' => $action('cleaning.incidents.record', 'field_ops.record_incident', 'Record one safety or service incident.', 'high', true, 'incidents.create', ['job_id', 'description']),
];

return [
    'vertical' => 'cleaning',
    'service_types' => ['residential_cleaning', 'commercial_cleaning', 'pool_cleaning', 'pressure_washing', 'car_detailing'],
    'cleaning_tools' => array_values(array_map(static fn (array $item): array => $item['tool'], $definitions)),
    'cleaning_actions' => array_combine(
        array_map(static fn (array $item): string => $item['tool']['target'], $definitions),
        array_map(static fn (array $item): array => $item['action'], $definitions),
    ),
    'operation_tool_map' => array_combine(
        array_keys($definitions),
        array_map(static fn (array $item): string => $item['tool']['name'], $definitions),
    ),

    /*
     * Host bindings must point to authoritative WorkCore application services:
     *   'jobs.create_job_order' => App\Domains\WorkCore\Jobs\CreateJob::class.'@handle'
     * Unconfigured actions fail closed and never return simulated success.
     */
    'host_action_handlers' => [],
];
