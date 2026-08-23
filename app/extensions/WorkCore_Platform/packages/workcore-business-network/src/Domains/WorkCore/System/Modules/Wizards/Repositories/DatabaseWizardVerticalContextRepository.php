<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Repositories;

use App\Domains\WorkCore\System\Modules\Wizards\Contracts\WizardVerticalContextRepositoryContract;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardDefinitionRegistry;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;
use JsonException;

final class DatabaseWizardVerticalContextRepository implements WizardVerticalContextRepositoryContract
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly WizardDefinitionRegistry $definitions,
    ) {}

    public function snapshot(string $runPublicId, int $companyId): array
    {
        $run = $this->db->table('tz_wizard_runs')
            ->where('company_id', $companyId)
            ->where('public_id', $runPublicId)
            ->first();

        if (!$run) {
            throw new InvalidArgumentException('Wizard run does not belong to the active company.');
        }

        $metadata = $this->decodeNullableJson($run->metadata ?? null, 'wizard run metadata');
        $definition = $this->definitions->getVersion(
            (string) $run->definition_key,
            (int) $run->definition_version,
            $companyId,
        );

        $answerRows = $this->db->table('tz_wizard_answers')
            ->where('company_id', $companyId)
            ->where('run_id', $run->id)
            ->orderBy('id')
            ->get();

        $answers = [];
        foreach ($answerRows as $row) {
            $answers[] = [
                'public_id' => (string) $row->public_id,
                'question_key' => (string) $row->question_key,
                'value' => $this->decodeNullableJson($row->value ?? null, 'wizard answer value'),
                'source' => (string) ($row->source ?? 'existing_company_data'),
                'confidence' => $row->confidence === null ? null : (float) $row->confidence,
                'is_confirmed' => (bool) ($row->is_confirmed ?? false),
                'answered_by_user_id' => $row->answered_by_user_id === null ? null : (int) $row->answered_by_user_id,
                'answered_by_agent_public_id' => $row->answered_by_agent_public_id === null
                    ? null
                    : (string) $row->answered_by_agent_public_id,
                'created_at' => $this->timestamp($row->created_at ?? null),
                'updated_at' => $this->timestamp($row->updated_at ?? null),
            ];
        }

        $approvalRows = $this->db->table('tz_wizard_section_approvals')
            ->where('company_id', $companyId)
            ->where('run_id', $run->id)
            ->orderBy('id')
            ->get();

        $approvals = [];
        foreach ($approvalRows as $row) {
            $approvals[(string) $row->section_key] = [
                'public_id' => (string) $row->public_id,
                'status' => (string) $row->status,
                'summary' => $this->decodeNullableJson($row->summary ?? null, 'wizard approval summary'),
                'approved_by_user_id' => $row->approved_by_user_id === null ? null : (int) $row->approved_by_user_id,
                'approved_at' => $this->timestamp($row->approved_at ?? null),
                'updated_at' => $this->timestamp($row->updated_at ?? null),
            ];
        }

        return [
            'run' => [
                'id' => (int) $run->id,
                'public_id' => (string) $run->public_id,
                'company_id' => (int) $run->company_id,
                'definition_key' => (string) $run->definition_key,
                'definition_version' => (int) $run->definition_version,
                'status' => (string) $run->status,
                'mode' => (string) $run->mode,
                'current_section_key' => $run->current_section_key === null ? null : (string) $run->current_section_key,
                'current_question_key' => $run->current_question_key === null ? null : (string) $run->current_question_key,
                'initiated_by_user_id' => (int) $run->initiated_by_user_id,
                'assisted_by_agent_public_id' => $run->assisted_by_agent_public_id === null
                    ? null
                    : (string) $run->assisted_by_agent_public_id,
                'completion_percent' => (float) $run->completion_percent,
                'metadata' => $metadata,
                'started_at' => $this->timestamp($run->started_at ?? null),
                'paused_at' => $this->timestamp($run->paused_at ?? null),
                'completed_at' => $this->timestamp($run->completed_at ?? null),
                'created_at' => $this->timestamp($run->created_at ?? null),
                'updated_at' => $this->timestamp($run->updated_at ?? null),
            ],
            'definition_snapshot' => $definition,
            'answers' => $answers,
            'approved_sections' => $approvals,
            'answer_revision' => max(0, (int) ($metadata['answer_revision'] ?? count($answers))),
        ];
    }

    private function decodeNullableJson(mixed $value, string $label): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value) || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        try {
            return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException("Invalid {$label} JSON.", previous: $exception);
        }
    }

    private function timestamp(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        return (string) $value;
    }
}
