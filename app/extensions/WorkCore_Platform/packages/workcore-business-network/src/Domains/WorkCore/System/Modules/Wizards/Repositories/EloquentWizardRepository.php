<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Repositories;

use App\Domains\WorkCore\System\Modules\Wizards\Contracts\WizardRepositoryContract;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class EloquentWizardRepository implements WizardRepositoryContract
{
    public function __construct(private ConnectionInterface $db) {}

    public function createDefinition(array $definition, int $companyId, int $actorId): array
    {
        $publicId = (string) Str::ulid();
        $key = (string) $definition['key'];
        $version = (int) ($definition['version'] ?? 1);
        $this->db->table('tz_wizard_definitions')->insert([
            'public_id' => $publicId,
            'company_id' => $companyId,
            'definition_key' => $key,
            'version' => $version,
            'title' => (string) $definition['title'],
            'description' => $definition['description'] ?? null,
            'status' => 'draft',
            'definition' => json_encode($definition, JSON_THROW_ON_ERROR),
            'created_by_user_id' => $actorId,
            'updated_by_user_id' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['public_id' => $publicId, 'key' => $key, 'version' => $version, 'status' => 'draft'];
    }

    public function publishDefinition(string $publicId, int $companyId, int $actorId): array
    {
        $updated = $this->db->table('tz_wizard_definitions')
            ->where('company_id', $companyId)
            ->where('public_id', $publicId)
            ->update([
                'status' => 'published',
                'published_at' => now(),
                'updated_by_user_id' => $actorId,
                'updated_at' => now(),
            ]);
        if ($updated !== 1) {
            throw new InvalidArgumentException('Wizard definition does not belong to the active company.');
        }

        return ['public_id' => $publicId, 'status' => 'published'];
    }

    public function startRun(array $definition, array $data, int $companyId, int $actorId): array
    {
        $publicId = (string) Str::ulid();
        $first = $definition['sections'][0]['key'] ?? null;
        $metadata = (array) ($data['metadata'] ?? []);
        $metadata['answer_revision'] = max(0, (int) ($metadata['answer_revision'] ?? 0));
        $this->db->table('tz_wizard_runs')->insert([
            'public_id' => $publicId,
            'company_id' => $companyId,
            'definition_key' => $definition['key'],
            'definition_version' => $definition['version'] ?? 1,
            'status' => 'in_progress',
            'mode' => $data['mode'] ?? 'hybrid',
            'current_section_key' => $first,
            'initiated_by_user_id' => $actorId,
            'assisted_by_agent_public_id' => $data['agent_public_id'] ?? null,
            'completion_percent' => 0,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->event(
            $companyId,
            $publicId,
            'wizard.run.started',
            ['definition_key' => $definition['key'], 'mode' => $data['mode'] ?? 'hybrid'],
            'user',
            (string) $actorId,
        );

        return ['public_id' => $publicId, 'definition_key' => $definition['key'], 'status' => 'in_progress', 'mode' => $data['mode'] ?? 'hybrid'];
    }

    public function saveAnswer(string $runPublicId, array $question, mixed $value, array $provenance, int $companyId, int $actorId): array
    {
        $run = $this->runRow($runPublicId, $companyId);
        $existing = $this->db->table('tz_wizard_answers')
            ->where('company_id', $companyId)
            ->where('run_id', $run->id)
            ->where('question_key', $question['key'])
            ->first();

        $encodedValue = $this->canonicalJson($value);
        $source = (string) ($provenance['source'] ?? 'user_entered');
        $confidence = isset($provenance['confidence']) ? (float) $provenance['confidence'] : null;
        $confirmed = (bool) ($provenance['is_confirmed'] ?? ($source === 'user_entered'));
        $agentPublicId = isset($provenance['agent_public_id']) && $provenance['agent_public_id'] !== ''
            ? (string) $provenance['agent_public_id']
            : null;
        $affectedSections = array_values(array_unique(array_map(
            static fn (mixed $section): string => trim((string) $section),
            (array) ($question['_affected_sections'] ?? [$question['_section_key'] ?? '']),
        )));
        $affectedSections = array_values(array_filter($affectedSections, static fn (string $section): bool => $section !== ''));
        sort($affectedSections, SORT_STRING);
        if ($affectedSections === []) {
            throw new InvalidArgumentException('Wizard answer has no affected section.');
        }

        $metadata = $this->decodeMetadata($run->metadata ?? null);
        $currentRevision = max(0, (int) ($metadata['answer_revision'] ?? 0));
        $same = $existing !== null
            && (string) $existing->value === $encodedValue
            && (string) $existing->source === $source
            && $this->sameNullableFloat($existing->confidence, $confidence)
            && (bool) $existing->is_confirmed === $confirmed
            && ($existing->answered_by_agent_public_id ?? null) === $agentPublicId;

        if ($same) {
            return [
                'public_id' => (string) $existing->public_id,
                'run_public_id' => $runPublicId,
                'question_key' => (string) $question['key'],
                'source' => $source,
                'confidence' => $confidence,
                'is_confirmed' => $confirmed,
                'risk' => (string) ($question['risk'] ?? 'low'),
                'affected_sections' => $affectedSections,
                'answer_revision' => $currentRevision,
                'changed' => false,
                'change_event' => null,
            ];
        }

        $answerData = [
            'company_id' => $companyId,
            'run_id' => $run->id,
            'question_key' => $question['key'],
            'value' => $encodedValue,
            'source' => $source,
            'confidence' => $confidence,
            'is_confirmed' => $confirmed,
            'answered_by_user_id' => $actorId,
            'answered_by_agent_public_id' => $agentPublicId,
            'updated_at' => now(),
        ];
        if ($existing) {
            $this->db->table('tz_wizard_answers')->where('id', $existing->id)->update($answerData);
            $answerPublicId = (string) $existing->public_id;
        } else {
            $answerPublicId = (string) Str::ulid();
            $this->db->table('tz_wizard_answers')->insert([
                'public_id' => $answerPublicId,
                'created_at' => now(),
                ...$answerData,
            ]);
        }

        $answerRevision = $currentRevision + 1;
        $metadata['answer_revision'] = $answerRevision;
        $this->db->table('tz_wizard_runs')
            ->where('id', $run->id)
            ->where('company_id', $companyId)
            ->update(['metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'updated_at' => now()]);

        $this->db->table('tz_wizard_section_approvals')
            ->where('company_id', $companyId)
            ->where('run_id', $run->id)
            ->whereIn('section_key', $affectedSections)
            ->where('status', 'approved')
            ->update(['status' => 'stale', 'approved_at' => null, 'updated_at' => now()]);

        $eventPublicId = (string) Str::ulid();
        $changeEvent = [
            'event_public_id' => $eventPublicId,
            'company_id' => $companyId,
            'actor_id' => $actorId,
            'run_public_id' => $runPublicId,
            'answer_public_id' => $answerPublicId,
            'question_key' => (string) $question['key'],
            'answer_revision' => $answerRevision,
            'affected_sections' => $affectedSections,
            'source' => $source,
            'confidence' => $confidence ?? 0.0,
            'risk' => (string) ($question['risk'] ?? 'low'),
            'confirmed' => $confirmed,
            'idempotency_key' => (string) ($provenance['idempotency_key'] ?? $eventPublicId),
        ];
        $this->event(
            $companyId,
            $runPublicId,
            'wizard.answer.changed',
            ['change_event' => $changeEvent],
            $agentPublicId !== null ? 'ai' : 'user',
            $agentPublicId ?? (string) $actorId,
            $eventPublicId,
        );

        return [
            'public_id' => $answerPublicId,
            'run_public_id' => $runPublicId,
            'question_key' => (string) $question['key'],
            'source' => $source,
            'confidence' => $confidence,
            'is_confirmed' => $confirmed,
            'risk' => (string) ($question['risk'] ?? 'low'),
            'affected_sections' => $affectedSections,
            'answer_revision' => $answerRevision,
            'changed' => true,
            'change_event' => $changeEvent,
        ];
    }

    public function approveSection(string $runPublicId, string $sectionKey, array $summary, int $companyId, int $actorId): array
    {
        $run = $this->runRow($runPublicId, $companyId);
        $existing = $this->db->table('tz_wizard_section_approvals')->where('run_id', $run->id)->where('section_key', $sectionKey)->first();
        $data = [
            'company_id' => $companyId,
            'run_id' => $run->id,
            'section_key' => $sectionKey,
            'status' => 'approved',
            'summary' => json_encode($summary, JSON_THROW_ON_ERROR),
            'approved_by_user_id' => $actorId,
            'approved_at' => now(),
            'updated_at' => now(),
        ];
        if ($existing) {
            $this->db->table('tz_wizard_section_approvals')->where('id', $existing->id)->update($data);
        } else {
            $this->db->table('tz_wizard_section_approvals')->insert(['public_id' => (string) Str::ulid(), 'created_at' => now(), ...$data]);
        }
        $this->event($companyId, $runPublicId, 'wizard.section.approved', ['section_key' => $sectionKey], 'user', (string) $actorId);

        return ['run_public_id' => $runPublicId, 'section_key' => $sectionKey, 'status' => 'approved'];
    }

    public function changeStatus(string $runPublicId, string $status, int $companyId, int $actorId): array
    {
        $updates = ['status' => $status, 'updated_at' => now()];
        if ($status === 'paused') {
            $updates['paused_at'] = now();
        }
        if ($status === 'in_progress') {
            $updates['paused_at'] = null;
        }
        if ($status === 'completed') {
            $updates['completed_at'] = now();
            $updates['completion_percent'] = 100;
        }
        $updated = $this->db->table('tz_wizard_runs')->where('company_id', $companyId)->where('public_id', $runPublicId)->update($updates);
        if ($updated !== 1) {
            throw new InvalidArgumentException('Wizard run does not belong to the active company.');
        }
        $this->event($companyId, $runPublicId, 'wizard.run.' . $status, [], 'user', (string) $actorId);

        return ['public_id' => $runPublicId, 'status' => $status];
    }

    public function run(string $runPublicId, int $companyId): array
    {
        return (array) $this->runRow($runPublicId, $companyId);
    }

    public function answers(string $runPublicId, int $companyId): array
    {
        $run = $this->runRow($runPublicId, $companyId);
        $rows = $this->db->table('tz_wizard_answers')->where('company_id', $companyId)->where('run_id', $run->id)->get();
        $answers = [];
        foreach ($rows as $row) {
            $answers[$row->question_key] = json_decode((string) $row->value, true);
        }

        return $answers;
    }

    public function approvedSections(string $runPublicId, int $companyId): array
    {
        $run = $this->runRow($runPublicId, $companyId);

        return $this->db->table('tz_wizard_section_approvals')
            ->where('company_id', $companyId)
            ->where('run_id', $run->id)
            ->where('status', 'approved')
            ->pluck('section_key')
            ->map(static fn ($value): string => (string) $value)
            ->all();
    }

    private function runRow(string $publicId, int $companyId): object
    {
        $row = $this->db->table('tz_wizard_runs')->where('company_id', $companyId)->where('public_id', $publicId)->first();
        if (!$row) {
            throw new InvalidArgumentException('Wizard run does not belong to the active company.');
        }

        return $row;
    }

    private function event(
        int $companyId,
        string $runPublicId,
        string $type,
        array $payload,
        string $actorType,
        ?string $actorPublicId,
        ?string $publicId = null,
    ): string {
        $run = $this->db->table('tz_wizard_runs')->where('company_id', $companyId)->where('public_id', $runPublicId)->first(['id']);
        if (!$run) {
            throw new InvalidArgumentException('Wizard run does not belong to the active company.');
        }
        $publicId ??= (string) Str::ulid();
        $this->db->table('tz_wizard_events')->insert([
            'public_id' => $publicId,
            'company_id' => $companyId,
            'run_id' => $run->id,
            'event_type' => $type,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'actor_type' => $actorType,
            'actor_public_id' => $actorPublicId,
            'created_at' => now(),
        ]);

        return $publicId;
    }

    private function decodeMetadata(mixed $metadata): array
    {
        if ($metadata === null || $metadata === '') {
            return [];
        }
        if (is_array($metadata)) {
            return $metadata;
        }

        return (array) json_decode((string) $metadata, true, 512, JSON_THROW_ON_ERROR);
    }

    private function canonicalJson(mixed $value): string
    {
        return json_encode($this->canonicalize($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function canonicalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map($this->canonicalize(...), $value);
        }
        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }

    private function sameNullableFloat(mixed $stored, ?float $expected): bool
    {
        if ($stored === null && $expected === null) {
            return true;
        }
        if ($stored === null || $expected === null) {
            return false;
        }

        return abs((float) $stored - $expected) < 0.00001;
    }
}
