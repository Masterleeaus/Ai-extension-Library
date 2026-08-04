<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Booking;

use App\Extensions\TitanAIGovernance\System\Contracts\WorkCoreGateway;
use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolDefinition;
use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolExecutor;
use InvalidArgumentException;

final class WorkCoreBookingCoordinator
{
    public function __construct(
        private WorkCoreGateway $workCore,
        private GovernedToolExecutor $tools,
    ) {}

    public function availability(array $request, array $context): array
    {
        $this->requireFields($request, ['service_type', 'from', 'to']);
        return $this->workCore->read('scheduling', 'check_availability', [
            ...$request,
            'tenant_id' => $context['tenant_id'] ?? null,
        ]);
    }

    public function book(array $request, array $context): array
    {
        $this->requireFields($request, ['customer', 'service_type', 'scheduled_at', 'duration_minutes', 'location']);
        $availability = $this->availability([
            'service_type' => $request['service_type'],
            'from' => $request['scheduled_at'],
            'to' => $request['scheduled_until'] ?? $request['scheduled_at'],
            'worker_id' => $request['worker_id'] ?? null,
            'location' => $request['location'],
        ], $context);

        if (! ($availability['available'] ?? false)) {
            return ['status' => 'rejected', 'reason' => 'unavailable', 'availability' => $availability];
        }

        return $this->tools->execute(
            new GovernedToolDefinition(
                'workcore.book_job',
                'jobs',
                'book_job',
                'amber',
                ['jobs.create'],
                true,
                true,
                true,
            ),
            $request,
            [
                ...$context,
                'facts' => ['availability' => $availability],
                'constraints' => ['workcore_is_authority' => true, 'direct_database_writes_forbidden' => true],
            ],
        );
    }

    public function reschedule(string $jobId, array $request, array $context): array
    {
        $this->requireFields($request, ['scheduled_at']);
        return $this->tools->execute(
            new GovernedToolDefinition('workcore.reschedule_job', 'jobs', 'reschedule_job', 'amber', ['jobs.update'], true, true, true),
            ['job_id' => $jobId, ...$request],
            $context,
        );
    }

    public function cancel(string $jobId, ?string $reason, array $context): array
    {
        return $this->tools->execute(
            new GovernedToolDefinition('workcore.cancel_job', 'jobs', 'cancel_job', 'red', ['jobs.cancel'], true, true, true),
            ['job_id' => $jobId, 'reason' => $reason],
            $context,
        );
    }

    private function requireFields(array $payload, array $fields): void
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                throw new InvalidArgumentException("Missing required booking field: {$field}");
            }
        }
    }
}
