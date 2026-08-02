<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Services;

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMediaAutomation\System\Models\Automation;
use App\Extensions\SocialMediaAutomation\System\Models\AutomationLog;
use App\Extensions\SocialMediaAutomation\System\Models\PendingAutomation;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AutomationExecutionService
{
    /**
     * Process an incoming comment event from a platform webhook.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processCommentEvent(string $platform, array $payload): void
    {
        $accountId = $payload['account_id'] ?? null;

        Log::debug('Processing comment event', [
            'platform'   => $platform,
            'account_id' => $accountId,
            'post_id'    => $payload['post_id'] ?? null,
            'comment_id' => $payload['comment_id'] ?? null,
            'text'       => $payload['text'] ?? '',
        ]);

        $automations = Automation::query()
            ->where('status', 'live')
            ->whereHas('platform', function ($query) use ($platform, $accountId) {
                $query->where('platform', $platform);
                if ($accountId) {
                    $query->where('credentials->platform_id', $accountId);
                }
            })
            ->with(['actions', 'replies', 'platform'])
            ->get();

        Log::debug('Automations found for platform', [
            'platform' => $platform,
            'count'    => $automations->count(),
            'ids'      => $automations->pluck('id')->toArray(),
        ]);

        foreach ($automations as $automation) {
            $matched = $this->matchesTrigger($automation, $payload);

            Log::debug('Automation trigger check', [
                'automation_id'   => $automation->id,
                'automation_name' => $automation->name ?? null,
                'matched'         => $matched,
            ]);

            if ($matched) {
                $delay = max(0, $automation->delay_seconds);

                PendingAutomation::query()->create([
                    'automation_id' => $automation->id,
                    'comment_data'  => $payload,
                    'execute_at'    => now()->addSeconds($delay),
                    'status'        => 'pending',
                ]);

                Log::debug('Pending automation created', [
                    'automation_id' => $automation->id,
                    'execute_at'    => now()->addSeconds($delay)->toDateTimeString(),
                    'delay_seconds' => $delay,
                ]);
            }
        }
    }

    /**
     * Check if a comment matches the automation's trigger rules.
     *
     * @param  array<string, mixed>  $commentData
     */
    public function matchesTrigger(Automation $automation, array $commentData): bool
    {
        // Check post targeting
        if ($automation->trigger_target === 'specific_post') {
            $postId = $commentData['post_id'] ?? null;

            if ($postId !== $automation->trigger_post_id) {
                Log::debug('Trigger mismatch: post_id does not match', [
                    'automation_id'   => $automation->id,
                    'expected_post'   => $automation->trigger_post_id,
                    'received_post'   => $postId,
                ]);

                return false;
            }
        }

        // Check keyword matching
        if ($automation->keyword_mode === 'specific') {
            $commentText = strtolower($commentData['text'] ?? '');

            // Check include keywords
            $includeKeywords = $automation->include_keywords ?? [];
            if (! empty($includeKeywords)) {
                $found = false;
                foreach ($includeKeywords as $keyword) {
                    if (str_contains($commentText, strtolower($keyword))) {
                        $found = true;

                        break;
                    }
                }
                if (! $found) {
                    Log::debug('Trigger mismatch: no include keyword found in comment', [
                        'automation_id'    => $automation->id,
                        'comment_text'     => $commentText,
                        'include_keywords' => $includeKeywords,
                    ]);

                    return false;
                }
            }

            // Check exclude keywords
            $excludeKeywords = $automation->exclude_keywords ?? [];
            foreach ($excludeKeywords as $keyword) {
                if (str_contains($commentText, strtolower($keyword))) {
                    Log::debug('Trigger mismatch: exclude keyword found in comment', [
                        'automation_id'   => $automation->id,
                        'comment_text'    => $commentText,
                        'matched_keyword' => $keyword,
                    ]);

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Execute automation actions for a matched comment.
     *
     * @param  array<string, mixed>  $commenterData
     */
    public function executeActions(Automation $automation, array $commenterData): void
    {
        $commentId = $commenterData['comment_id'] ?? null;

        Log::debug('Executing automation actions', [
            'automation_id' => $automation->id,
            'comment_id'    => $commentId,
            'commenter_id'  => $commenterData['commenter_id'] ?? null,
            'platform'      => $automation->platform->platform ?? null,
            'actions_count' => $automation->actions->count(),
            'replies_count' => $automation->replies->count(),
        ]);

        if ($commentId) {
            $exists = AutomationLog::query()
                ->where('automation_id', $automation->id)
                ->where('platform_comment_id', $commentId)
                ->exists();

            if ($exists) {
                Log::debug('Automation skipped: already executed for this comment', [
                    'automation_id' => $automation->id,
                    'comment_id'    => $commentId,
                ]);

                return;
            }
        }

        $log = AutomationLog::query()->create([
            'automation_id'       => $automation->id,
            'platform_comment_id' => $commenterData['comment_id'] ?? null,
            'commenter_id'        => $commenterData['commenter_id'] ?? null,
            'commenter_username'  => $commenterData['commenter_username'] ?? null,
            'comment_text'        => $commenterData['text'] ?? null,
            'status'              => 'success',
        ]);

        try {
            $variables = $this->buildVariables($commenterData);

            // Send public reply if enabled
            if ($automation->enable_public_replies && $automation->replies->isNotEmpty()) {
                $replyText = $automation->replies->random()->content;
                $replyText = $this->substituteVariables($replyText, $variables);
                $this->sendPublicReply(
                    $automation->platform,
                    $commenterData['comment_id'] ?? '',
                    $replyText
                );
            }

            // Send DM actions with variable substitution
            if ($automation->actions->isNotEmpty()) {
                $processedActions = $this->processActionsWithVariables(
                    $automation->actions->toArray(),
                    $variables
                );
                $this->sendDm(
                    $automation->platform,
                    $commenterData,
                    $processedActions
                );
            }

            $log->update([
                'actions_executed' => $automation->actions->pluck('type')->toArray(),
            ]);
        } catch (Throwable $e) {
            Log::error('Automation execution failed', [
                'automation_id' => $automation->id,
                'error'         => $e->getMessage(),
            ]);

            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build the variable map from commenter data.
     *
     * @param  array<string, mixed>  $commenterData
     *
     * @return array<string, string>
     */
    private function buildVariables(array $commenterData): array
    {
        $username = $commenterData['commenter_username'] ?? '';
        $firstName = $commenterData['commenter_first_name']
            ?? explode(' ', $commenterData['commenter_name'] ?? $username)[0]
            ?? $username;

        return [
            '{first_name}'   => $firstName,
            '{username}'     => $username,
            '{comment_text}' => $commenterData['text'] ?? '',
        ];
    }

    /**
     * Replace variable placeholders in a text string.
     *
     * @param  array<string, string>  $variables
     */
    private function substituteVariables(string $text, array $variables): string
    {
        return str_replace(
            array_keys($variables),
            array_values($variables),
            $text
        );
    }

    /**
     * Process all actions and substitute variables in text content.
     *
     * @param  array<int, mixed>  $actions
     * @param  array<string, string>  $variables
     *
     * @return array<int, mixed>
     */
    private function processActionsWithVariables(array $actions, array $variables): array
    {
        return array_map(function (array $action) use ($variables) {
            $content = $action['content'] ?? [];

            if ($action['type'] === 'text' && isset($content['text'])) {
                $content['text'] = $this->substituteVariables($content['text'], $variables);
            }

            if ($action['type'] === 'button' && isset($content['label'])) {
                $content['label'] = $this->substituteVariables($content['label'], $variables);
            }

            $action['content'] = $content;

            return $action;
        }, $actions);
    }

    /**
     * Send a DM to a user via the platform API.
     *
     * Instagram and Facebook use Private Replies (recipient.comment_id),
     * while X/Twitter uses standard DM (recipient user ID).
     *
     * @param  array<string, mixed>  $commenterData
     * @param  array<int, mixed>  $actions
     */
    public function sendDm(SocialMediaPlatform $platform, array $commenterData, array $actions): void
    {
        $platformName = $platform->platform;

        // Platform-specific DM sending
        match ($platformName) {
            'instagram' => $this->sendInstagramDm($platform, $commenterData['comment_id'] ?? '', $actions),
            'facebook'  => $this->sendFacebookDm($platform, $commenterData['comment_id'] ?? '', $actions),
            'x', 'twitter' => $this->sendXDm($platform, $commenterData['commenter_id'] ?? '', $actions),
            default     => Log::warning("DM not supported for platform: {$platformName}"),
        };
    }

    /**
     * Send a public reply to a comment via the platform API.
     */
    public function sendPublicReply(SocialMediaPlatform $platform, string $commentId, string $replyText): void
    {
        $platformName = $platform->platform;

        match ($platformName) {
            'instagram' => $this->replyInstagramComment($platform, $commentId, $replyText),
            'facebook'  => $this->replyFacebookComment($platform, $commentId, $replyText),
            'tiktok'    => $this->replyTikTokComment($platform, $commentId, $replyText),
            'linkedin'  => $this->replyLinkedInComment($platform, $commentId, $replyText),
            'x', 'twitter' => $this->replyXComment($platform, $commentId, $replyText),
            default     => Log::warning("Comment reply not supported for platform: {$platformName}"),
        };
    }

    // ──────────────────────────────────────────────────────────
    //  Platform-specific DM implementations
    // ──────────────────────────────────────────────────────────

    /**
     * Send DM via Instagram Graph API Private Reply (POST /{ig-user-id}/messages).
     *
     * Uses comment_id as recipient to trigger a Private Reply to the commenter.
     *
     * @param  array<int, mixed>  $actions
     */
    private function sendInstagramDm(SocialMediaPlatform $platform, string $commentId, array $actions): void
    {
        $accessToken = $platform->credentials['access_token'] ?? null;
        $igUserId = $platform->credentials['platform_id'] ?? $platform->credentials['id'] ?? null;

        if (! $accessToken || ! $igUserId) {
            Log::warning('Instagram DM skipped: missing credentials', ['platform_id' => $platform->id]);

            return;
        }

        if (! $commentId) {
            Log::warning('Instagram DM skipped: missing comment_id', ['platform_id' => $platform->id]);

            return;
        }

        $apiVersion = config('social-media.instagram.api_version', 'v18.0');
        $baseUrl = "https://graph.facebook.com/{$apiVersion}";

        foreach ($actions as $action) {
            $message = $this->buildInstagramMessage($action);

            if (! $message) {
                continue;
            }

            if ($action['type'] === 'delay') {
                sleep(min((int) ($action['content']['seconds'] ?? 1), 60));

                continue;
            }

            $response = $this->graphApiPost("{$baseUrl}/{$igUserId}/messages", $accessToken, [
                'recipient' => ['comment_id' => $commentId],
                'message'   => $message,
            ]);

            $this->logApiResponse('Instagram DM (private reply)', $response);
        }
    }

    /**
     * Build an Instagram-compatible message payload from an action.
     *
     * @param  array<string, mixed>  $action
     *
     * @return array<string, mixed>|null
     */
    private function buildInstagramMessage(array $action): ?array
    {
        $content = $action['content'] ?? [];

        return match ($action['type'] ?? '') {
            'text'          => ['text' => $content['text'] ?? ''],
            'image'         => ['attachment' => ['type' => 'image', 'payload' => ['url' => $content['url'] ?? '']]],
            'button'        => ['attachment' => ['type' => 'template', 'payload' => [
                'template_type' => 'button',
                'text'          => $content['label'] ?? 'Click here',
                'buttons'       => [['type' => 'web_url', 'url' => $content['url'] ?? '', 'title' => $content['label'] ?? '']],
            ]]],
            'quick_replies' => ['text' => 'Choose an option:', 'quick_replies' => collect($content['items'] ?? [])
                ->map(fn (string $item) => ['content_type' => 'text', 'title' => $item, 'payload' => $item])
                ->toArray()],
            default => null,
        };
    }

    /**
     * Send DM via Facebook Private Reply API (POST /me/messages).
     *
     * Uses comment_id as recipient to trigger a Private Reply to the commenter.
     *
     * @param  array<int, mixed>  $actions
     */
    private function sendFacebookDm(SocialMediaPlatform $platform, string $commentId, array $actions): void
    {
        $accessToken = $platform->credentials['access_token'] ?? null;

        if (! $accessToken) {
            Log::warning('Facebook DM skipped: missing access token', ['platform_id' => $platform->id]);

            return;
        }

        if (! $commentId) {
            Log::warning('Facebook DM skipped: missing comment_id', ['platform_id' => $platform->id]);

            return;
        }

        $apiVersion = config('social-media.facebook.api_version', 'v18.0');
        $baseUrl = "https://graph.facebook.com/{$apiVersion}";

        Log::debug('Facebook DM sending', [
            'platform_id'   => $platform->id,
            'comment_id'    => $commentId,
            'api_version'   => $apiVersion,
            'actions_count' => count($actions),
            'action_types'  => array_column($actions, 'type'),
        ]);

        foreach ($actions as $action) {
            if ($action['type'] === 'delay') {
                $seconds = min((int) ($action['content']['seconds'] ?? 1), 60);
                Log::debug('Facebook DM delay action', ['seconds' => $seconds]);
                sleep($seconds);

                continue;
            }

            $message = $this->buildFacebookMessage($action);

            if (! $message) {
                Log::debug('Facebook DM action skipped: unsupported type', ['type' => $action['type'] ?? null]);

                continue;
            }

            Log::debug('Facebook DM request', [
                'url'       => "{$baseUrl}/me/messages",
                'recipient' => ['comment_id' => $commentId],
                'message'   => $message,
            ]);

            $response = $this->graphApiPost("{$baseUrl}/me/messages", $accessToken, [
                'recipient' => ['comment_id' => $commentId],
                'message'   => $message,
            ]);

            $this->logApiResponse('Facebook DM (private reply)', $response);
        }
    }

    /**
     * Build a Facebook-compatible message payload from an action.
     *
     * @param  array<string, mixed>  $action
     *
     * @return array<string, mixed>|null
     */
    private function buildFacebookMessage(array $action): ?array
    {
        $content = $action['content'] ?? [];

        return match ($action['type'] ?? '') {
            'text'          => ['text' => $content['text'] ?? ''],
            'image'         => ['attachment' => ['type' => 'image', 'payload' => ['url' => $content['url'] ?? '', 'is_reusable' => true]]],
            'button'        => ['attachment' => ['type' => 'template', 'payload' => [
                'template_type' => 'button',
                'text'          => $content['label'] ?? 'Click here',
                'buttons'       => [['type' => 'web_url', 'url' => $content['url'] ?? '', 'title' => $content['label'] ?? '']],
            ]]],
            'quick_replies' => ['text' => 'Choose an option:', 'quick_replies' => collect($content['items'] ?? [])
                ->map(fn (string $item) => ['content_type' => 'text', 'title' => $item, 'payload' => $item])
                ->toArray()],
            default => null,
        };
    }

    /**
     * Send DM via X API v2 (POST /2/dm_conversations/with/{participant_id}/messages).
     *
     * @param  array<int, mixed>  $actions
     */
    private function sendXDm(SocialMediaPlatform $platform, string $recipientId, array $actions): void
    {
        $accessToken = $platform->credentials['access_token'] ?? null;

        if (! $accessToken) {
            Log::warning('X DM skipped: missing access token', ['platform_id' => $platform->id]);

            return;
        }

        $baseUrl = 'https://api.x.com/2';

        foreach ($actions as $action) {
            if ($action['type'] === 'delay') {
                sleep(min((int) ($action['content']['seconds'] ?? 1), 60));

                continue;
            }

            $content = $action['content'] ?? [];
            $text = match ($action['type'] ?? '') {
                'text'          => $content['text'] ?? '',
                'button'        => ($content['label'] ?? '') . "\n" . ($content['url'] ?? ''),
                'quick_replies' => 'Options: ' . implode(', ', $content['items'] ?? []),
                default         => null,
            };

            if (! $text) {
                continue;
            }

            $response = Http::withToken($accessToken)
                ->retry(2, 1000)
                ->post("{$baseUrl}/dm_conversations/with/{$recipientId}/messages", [
                    'text' => $text,
                ]);

            $this->logApiResponse('X DM', $response);
        }
    }

    // ──────────────────────────────────────────────────────────
    //  Platform-specific comment reply implementations
    // ──────────────────────────────────────────────────────────

    /**
     * Reply to an Instagram comment via Graph API (POST /{comment-id}/replies).
     */
    private function replyInstagramComment(SocialMediaPlatform $platform, string $commentId, string $replyText): void
    {
        $accessToken = $platform->credentials['access_token'] ?? null;

        if (! $accessToken) {
            return;
        }

        $apiVersion = config('social-media.instagram.api_version', 'v18.0');

        $response = $this->graphApiPost(
            "https://graph.facebook.com/{$apiVersion}/{$commentId}/replies",
            $accessToken,
            ['message' => $replyText]
        );

        $this->logApiResponse('Instagram comment reply', $response);
    }

    /**
     * Reply to a Facebook comment via Graph API (POST /{comment-id}/comments).
     */
    private function replyFacebookComment(SocialMediaPlatform $platform, string $commentId, string $replyText): void
    {
        $accessToken = $platform->credentials['access_token'] ?? null;

        if (! $accessToken) {
            return;
        }

        $apiVersion = config('social-media.facebook.api_version', 'v18.0');

        $response = $this->graphApiPost(
            "https://graph.facebook.com/{$apiVersion}/{$commentId}/comments",
            $accessToken,
            ['message' => $replyText]
        );

        $this->logApiResponse('Facebook comment reply', $response);
    }

    /**
     * Reply to a TikTok comment via TikTok API (POST /v2/comment/reply/create/).
     */
    private function replyTikTokComment(SocialMediaPlatform $platform, string $commentId, string $replyText): void
    {
        $accessToken = $platform->credentials['access_token'] ?? null;

        if (! $accessToken) {
            return;
        }

        $response = Http::withToken($accessToken)
            ->retry(2, 1000)
            ->post('https://open.tiktokapis.com/v2/comment/reply/create/', [
                'comment_id' => $commentId,
                'text'       => $replyText,
            ]);

        $this->logApiResponse('TikTok comment reply', $response);
    }

    /**
     * Reply to a LinkedIn comment via REST API (POST /socialActions/{id}/comments).
     */
    private function replyLinkedInComment(SocialMediaPlatform $platform, string $commentId, string $replyText): void
    {
        $accessToken = $platform->credentials['access_token'] ?? null;

        if (! $accessToken) {
            return;
        }

        $headerVersion = config('social-media.linkedin.header_version', '202408');

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'LinkedIn-Version'          => $headerVersion,
                'X-Restli-Protocol-Version' => '2.0.0',
            ])
            ->retry(2, 1000)
            ->post("https://api.linkedin.com/rest/socialActions/{$commentId}/comments", [
                'message' => ['text' => $replyText],
            ]);

        $this->logApiResponse('LinkedIn comment reply', $response);
    }

    /**
     * Reply to an X/Twitter post via API v2 (POST /2/tweets).
     */
    private function replyXComment(SocialMediaPlatform $platform, string $commentId, string $replyText): void
    {
        $accessToken = $platform->credentials['access_token'] ?? null;

        if (! $accessToken) {
            return;
        }

        $response = Http::withToken($accessToken)
            ->retry(2, 1000)
            ->post('https://api.x.com/2/tweets', [
                'text'  => $replyText,
                'reply' => ['in_reply_to_tweet_id' => $commentId],
            ]);

        $this->logApiResponse('X comment reply', $response);
    }

    // ──────────────────────────────────────────────────────────
    //  Helper methods
    // ──────────────────────────────────────────────────────────

    /**
     * Make a POST request to the Meta Graph API.
     *
     * @param  array<string, mixed>  $data
     */
    private function graphApiPost(string $url, string $accessToken, array $data): Response
    {
        return Http::withToken($accessToken)
            ->retry(2, 1000)
            ->post($url, $data);
    }

    /**
     * Log the API response for debugging.
     */
    private function logApiResponse(string $context, Response $response): void
    {
        if ($response->successful()) {
            Log::info("{$context} sent successfully", ['response' => $response->json()]);

            return;
        }

        Log::error("{$context} failed", [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        throw new RuntimeException("{$context} failed with status {$response->status()}");
    }
}
