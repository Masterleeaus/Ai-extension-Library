<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Repositories;

use App\Domains\WorkCore\System\Modules\Wizards\Contracts\WizardRecompositionRepositoryContract;
use App\Domains\WorkCore\System\Modules\Wizards\Events\WizardAnswerChanged;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

final class DatabaseWizardRecompositionRepository implements WizardRecompositionRepositoryContract
{
    public function __construct(private ConnectionInterface $db) {}

    public function recordDeterministicDrafts(WizardAnswerChanged $event, array $context): array
    {
        $this->assertContext($event, $context);

        return $this->db->transaction(function () use ($event, $context): array {
            $run = $this->run($event);
            $drafts = [];
            foreach ($event->affectedSections as $section) {
                $publicId = $this->stableId('draft', $event->eventPublicId, $section);
                $existing = $this->eventByPublicId($publicId, $event->companyId, (int) $run->id);
                if ($existing !== null) {
                    $drafts[] = $existing;
                    continue;
                }

                $previous = $this->latestProposal($event->companyId, (int) $run->id, $section);
                $payload = [
                    'schema_version' => '1.0.0',
                    'proposal_revision' => ((int) ($previous['proposal_revision'] ?? 0)) + 1,
                    'section_key' => $section,
                    'context_hash' => (string) $context['context_hash'],
                    'answer_revision' => $event->answerRevision,
                    'source' => 'deterministic',
                    'confidence' => 1.0,
                    'risk' => $event->risk,
                    'confirmation_state' => 'draft',
                    'trigger_event_public_id' => $event->eventPublicId,
                    'trigger_question_key' => $event->questionKey,
                    'supersedes_public_id' => $previous['public_id'] ?? null,
                    'preview' => $this->boundedPreview($context, $section),
                ];
                $this->insertEvent($publicId, $event, (int) $run->id, 'wizard.context.proposal.drafted', $payload, 'system');
                $drafts[] = ['public_id' => $publicId, ...$payload];
            }

            return $drafts;
        });
    }

    public function recordAIRevisions(WizardAnswerChanged $event, array $proposal): array
    {
        if ((string) ($proposal['context_hash'] ?? '') === '' || (string) ($proposal['proposal_id'] ?? '') === '') {
            throw new InvalidArgumentException('AI proposal revision is missing identity metadata.');
        }

        return $this->db->transaction(function () use ($event, $proposal): array {
            $run = $this->run($event);
            $rows = [];
            foreach ($event->affectedSections as $section) {
                $publicId = $this->stableId('ai', $event->eventPublicId . '|' . $proposal['proposal_id'], $section);
                $existing = $this->eventByPublicId($publicId, $event->companyId, (int) $run->id);
                if ($existing !== null) {
                    $rows[] = $existing;
                    continue;
                }

                $previous = $this->latestProposal($event->companyId, (int) $run->id, $section);
                $payload = [
                    'schema_version' => '1.0.0',
                    'proposal_revision' => ((int) ($previous['proposal_revision'] ?? 0)) + 1,
                    'section_key' => $section,
                    'context_hash' => (string) $proposal['context_hash'],
                    'answer_revision' => $event->answerRevision,
                    'source' => 'ai',
                    'confidence' => (float) ($proposal['confidence'] ?? 0.0),
                    'risk' => $event->risk,
                    'confirmation_state' => 'draft',
                    'trigger_event_public_id' => $event->eventPublicId,
                    'trigger_question_key' => $event->questionKey,
                    'supersedes_public_id' => $previous['public_id'] ?? null,
                    'ai_proposal_id' => (string) $proposal['proposal_id'],
                    'provider_id' => (string) ($proposal['provider_id'] ?? ''),
                    'model_id' => (string) ($proposal['model_id'] ?? ''),
                    'updates' => (array) ($proposal['updates'] ?? []),
                    'rationale' => (string) ($proposal['rationale'] ?? ''),
                    'provenance' => (array) ($proposal['provenance'] ?? []),
                    'audit' => (array) ($proposal['audit'] ?? []),
                ];
                $this->insertEvent($publicId, $event, (int) $run->id, 'wizard.context.proposal.ai', $payload, 'ai');
                $rows[] = ['public_id' => $publicId, ...$payload];
            }

            return $rows;
        });
    }

    private function assertContext(WizardAnswerChanged $event, array $context): void
    {
        if ((string) ($context['context_hash'] ?? '') === '') {
            throw new InvalidArgumentException('Recomposition context hash is missing.');
        }
        if ((int) ($context['wizard']['answer_revision'] ?? -1) !== $event->answerRevision) {
            throw new InvalidArgumentException('Wizard answer change is stale for the current context revision.');
        }
    }

    private function run(WizardAnswerChanged $event): object
    {
        $run = $this->db->table('tz_wizard_runs')
            ->where('company_id', $event->companyId)
            ->where('public_id', $event->runPublicId)
            ->first(['id']);
        if (!$run) {
            throw new InvalidArgumentException('Wizard run does not belong to the active company.');
        }

        return $run;
    }

    private function latestProposal(int $companyId, int $runId, string $section): ?array
    {
        $rows = $this->db->table('tz_wizard_events')
            ->where('company_id', $companyId)
            ->where('run_id', $runId)
            ->whereIn('event_type', ['wizard.context.proposal.drafted', 'wizard.context.proposal.ai'])
            ->orderByDesc('id')
            ->get(['public_id', 'payload']);
        foreach ($rows as $row) {
            $payload = (array) json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR);
            if ((string) ($payload['section_key'] ?? '') === $section) {
                return ['public_id' => (string) $row->public_id, ...$payload];
            }
        }

        return null;
    }

    private function eventByPublicId(string $publicId, int $companyId, int $runId): ?array
    {
        $row = $this->db->table('tz_wizard_events')
            ->where('public_id', $publicId)
            ->where('company_id', $companyId)
            ->where('run_id', $runId)
            ->first(['public_id', 'payload']);
        if (!$row) {
            return null;
        }

        return ['public_id' => (string) $row->public_id, ...(array) json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR)];
    }

    private function insertEvent(
        string $publicId,
        WizardAnswerChanged $event,
        int $runId,
        string $type,
        array $payload,
        string $actorType,
    ): void {
        $this->db->table('tz_wizard_events')->insert([
            'public_id' => $publicId,
            'company_id' => $event->companyId,
            'run_id' => $runId,
            'event_type' => $type,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'actor_type' => $actorType,
            'actor_public_id' => (string) $event->actorId,
            'created_at' => now(),
        ]);
    }

    private function stableId(string $kind, string $identity, string $section): string
    {
        return 'wrp_' . substr(hash('sha256', $kind . '|' . $identity . '|' . $section), 0, 26);
    }

    private function boundedPreview(array $context, string $section): array
    {
        return [
            'wizard' => [
                'definition_key' => $context['wizard']['definition_key'] ?? null,
                'definition_version' => $context['wizard']['definition_version'] ?? null,
                'answer_revision' => $context['wizard']['answer_revision'] ?? null,
                'launch_phase' => $context['wizard']['launch_phase'] ?? null,
            ],
            'locale' => (array) ($context['locale'] ?? []),
            'approval' => $context['approvals'][$section] ?? null,
            'resolved' => array_intersect_key((array) ($context['resolved'] ?? []), array_flip([
                'terminology', 'theme', 'navigation', 'workspaces', 'capabilities', 'forms', 'checklists', 'widgets', 'ai', 'activation',
            ])),
        ];
    }
}
