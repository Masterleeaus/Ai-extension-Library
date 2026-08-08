<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Services;

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMedia\System\Services\EngagementGovernanceService;
use App\Extensions\SocialMediaAutomation\System\Models\Automation;
use App\Extensions\SocialMediaAutomation\System\Models\AutomationLog;
use App\Extensions\SocialMediaAutomation\System\Models\PendingAutomation;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GovernedAutomationExecutionService extends AutomationExecutionService
{
    public function __construct(private readonly EngagementGovernanceService $engagement) {}

    /**
     * Accept an inbound engagement, preserve existing automation trigger matching,
     * and queue only governed proposal work. This method never changes provider state.
     */
    public function processCommentEvent(string $platform, array $payload): void
    {
        $accountId = trim((string) ($payload['account_id'] ?? ''));
        $commentId = trim((string) ($payload['comment_id'] ?? ''));

        Log::debug('Processing governed comment event', [
            'platform' => $platform,
            'account_id_hash' => $accountId !== '' ? hash('sha256', $accountId) : null,
            'comment_id_hash' => $commentId !== '' ? hash('sha256', $commentId) : null,
        ]);

        $automations = Automation::query()
            ->where('status', 'live')
            ->whereHas('platform', function ($query) use ($platform, $accountId) {
                $query->where('platform', $platform);
                if ($accountId !== '') {
                    $query->where('credentials->platform_id', $accountId);
                }
            })
            ->with(['actions', 'replies', 'platform'])
            ->get();

        foreach ($automations as $automation) {
            if (! $automation->platform instanceof SocialMediaPlatform
                || ! parent::matchesTrigger($automation, $payload)) {
                continue;
            }

            try {
                $governed = $this->engagement->ingestWebhookEvent(
                    $automation->platform,
                    $this->normaliseEngagement($platform, $payload)
                );

                if ((bool) ($governed['duplicate'] ?? false)) {
                    continue;
                }

                $delay = max(0, (int) $automation->delay_seconds);
                PendingAutomation::query()->create([
                    'automation_id' => $automation->id,
                    'comment_data' => [
                        ...$payload,
                        'governed' => true,
                    ],
                    'execute_at' => now()->addSeconds($delay),
                    'status' => 'pending',
                ]);

                Log::debug('Governed proposal work queued', [
                    'automation_id' => $automation->id,
                    'comment_id_hash' => $commentId !== '' ? hash('sha256', $commentId) : null,
                    'delay_seconds' => $delay,
                ]);
            } catch (Throwable $exception) {
                Log::warning('Governed engagement ingestion failed closed', [
                    'automation_id' => $automation->id,
                    'exception' => $exception::class,
                ]);
            }
        }
    }

    /**
     * Execute delayed automation work by staging proposals/handoffs only.
     * No provider-changing method on the legacy parent is called.
     */
    public function executeActions(Automation $automation, array $commenterData): void
    {
        if (! $automation->platform instanceof SocialMediaPlatform) {
            throw new RuntimeException('The automation provider account is unavailable.');
        }

        $commentId = trim((string) ($commenterData['comment_id'] ?? ''));

        if ($commentId !== '' && AutomationLog::query()
            ->where('automation_id', $automation->id)
            ->where('platform_comment_id', $commentId)
            ->exists()) {
            Log::debug('Governed automation skipped as duplicate', [
                'automation_id' => $automation->id,
                'comment_id_hash' => hash('sha256', $commentId),
            ]);

            return;
        }

        $log = AutomationLog::query()->create([
            'automation_id' => $automation->id,
            'platform_comment_id' => $commentId !== '' ? $commentId : null,
            'commenter_id' => $commenterData['commenter_id'] ?? null,
            'commenter_username' => $commenterData['commenter_username'] ?? null,
            'comment_text' => null,
            'status' => 'success',
        ]);

        try {
            $result = $this->stageGovernedProposal($automation, $commenterData);
            $actions = ['proposal_staged'];

            if ((bool) ($result['human_handoff_required'] ?? false)) {
                $this->engagement->handoff(
                    $automation->platform,
                    $automation->platform,
                    []
                );
            }

            $log->update([
                'actions_executed' => $actions,
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            Log::error('Governed automation proposal failed', [
                'automation_id' => $automation->id,
                'exception' => $exception::class,
            ]);

            $log->update([
                'status' => 'failed',
                'error_message' => $exception::class,
            ]);
        }
    }

    /**
     * Stage a single approval proposal from the existing automation reply/action rules.
     */
    public function stageGovernedProposal(Automation $automation, array $commenterData): array
    {
        $platform = $automation->platform;

        if (! $platform instanceof SocialMediaPlatform) {
            throw new RuntimeException('The automation provider account is unavailable.');
        }

        $suggestedText = $this->suggestedText($automation, $commenterData);
        $proposal = $this->engagement->proposeReply(
            $platform->user()->firstOrFail(),
            $platform,
            $this->normaliseEngagement((string) $platform->platform, $commenterData),
            $suggestedText
        );

        if ((bool) ($proposal['human_handoff_required'] ?? false)) {
            $this->engagement->handoff(
                $platform->user()->firstOrFail(),
                $platform,
                $this->normaliseEngagement((string) $platform->platform, $commenterData)
            );
        }

        return $proposal;
    }

    private function suggestedText(Automation $automation, array $commenterData): ?string
    {
        $template = null;

        if ($automation->enable_public_replies && $automation->replies->isNotEmpty()) {
            $template = (string) $automation->replies->first()->content;
        }

        if ($template === null && $automation->actions->isNotEmpty()) {
            foreach ($automation->actions as $action) {
                if (($action->type ?? null) !== 'text') {
                    continue;
                }

                $content = (array) ($action->content ?? []);
                if (! empty($content['text'])) {
                    $template = (string) $content['text'];
                    break;
                }
            }
        }

        if ($template === null || trim($template) === '') {
            return null;
        }

        $username = (string) ($commenterData['commenter_username'] ?? '');
        $name = (string) ($commenterData['commenter_name'] ?? $username);
        $firstName = trim(explode(' ', $name)[0] ?? $username);

        return str_replace(
            ['{first_name}', '{username}', '{comment_text}'],
            [$firstName, $username, (string) ($commenterData['text'] ?? '')],
            $template
        );
    }

    private function normaliseEngagement(string $platform, array $payload): array
    {
        $commentId = trim((string) ($payload['comment_id'] ?? $payload['engagement_id'] ?? ''));

        if ($commentId === '') {
            throw new RuntimeException('The provider engagement ID is missing.');
        }

        return [
            'engagement_id' => $commentId,
            'resource_id' => trim((string) ($payload['post_id'] ?? $payload['resource_id'] ?? $commentId)),
            'actor_id' => trim((string) ($payload['commenter_id'] ?? $payload['actor_id'] ?? '')),
            'actor_alias' => trim((string) ($payload['commenter_username'] ?? $payload['actor_alias'] ?? '')),
            'message' => trim((string) ($payload['text'] ?? $payload['message'] ?? '')),
            'received_at' => $payload['received_at'] ?? null,
            'provider_context' => [
                'source' => 'social-media-automation',
                'platform' => $platform,
            ],
        ];
    }
}
