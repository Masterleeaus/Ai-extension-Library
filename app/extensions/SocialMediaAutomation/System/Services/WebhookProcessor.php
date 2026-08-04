<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookProcessor
{
    public function __construct(
        private AutomationExecutionService $executionService
    ) {}

    public function executionService(): AutomationExecutionService
    {
        return $this->executionService;
    }

    /**
     * Process an Instagram webhook POST payload.
     *
     * Instagram sends comment events under entry[].changes[] with field = "comments".
     *
     * @param  array<string, mixed>  $payload
     */
    public function processInstagramPayload(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'comments') {
                    continue;
                }

                $value = $change['value'] ?? [];

                $this->executionService->processCommentEvent('instagram', [
                    'account_id'           => $entry['id'] ?? null,
                    'post_id'              => $value['media']['id'] ?? null,
                    'comment_id'           => $value['id'] ?? null,
                    'commenter_id'         => $value['from']['id'] ?? null,
                    'commenter_username'   => $value['from']['username'] ?? null,
                    'commenter_name'       => $value['from']['full_name'] ?? ($value['from']['username'] ?? null),
                    'text'                 => $value['text'] ?? '',
                ]);
            }
        }
    }

    /**
     * Process a Facebook webhook POST payload.
     *
     * Facebook sends comment events under entry[].changes[] with field = "feed"
     * and value.item = "comment".
     *
     * @param  array<string, mixed>  $payload
     */
    public function processFacebookPayload(array $payload): void
    {
        $entryCount = count($payload['entry'] ?? []);
        Log::debug('Facebook payload processing started', [
            'object'      => $payload['object'] ?? null,
            'entry_count' => $entryCount,
        ]);

        foreach ($payload['entry'] ?? [] as $entry) {
            $changeCount = count($entry['changes'] ?? []);
            Log::debug('Facebook entry processing', [
                'entry_id'     => $entry['id'] ?? null,
                'change_count' => $changeCount,
            ]);

            foreach ($entry['changes'] ?? [] as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                if ($field !== 'feed') {
                    Log::debug('Facebook change skipped: field is not feed', [
                        'field' => $field,
                    ]);

                    continue;
                }

                $item = $value['item'] ?? '';

                if ($item !== 'comment') {
                    Log::debug('Facebook feed change skipped: item is not comment', [
                        'item'    => $item,
                        'verb'    => $value['verb'] ?? null,
                        'post_id' => $value['post_id'] ?? null,
                    ]);

                    continue;
                }

                $commentData = [
                    'account_id'           => $entry['id'] ?? null,
                    'post_id'              => $value['post_id'] ?? null,
                    'comment_id'           => $value['comment_id'] ?? null,
                    'commenter_id'         => $value['sender_id'] ?? null,
                    'commenter_username'   => $value['sender_name'] ?? null,
                    'commenter_name'       => $value['sender_name'] ?? null,
                    'text'                 => $value['message'] ?? '',
                ];

                Log::debug('Facebook comment event extracted', $commentData);

                $this->executionService->processCommentEvent('facebook', $commentData);
            }
        }
    }

    /**
     * Process an X/Twitter Account Activity API webhook payload.
     *
     * X sends reply events under tweet_create_events where in_reply_to_status_id_str is set.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processXPayload(array $payload): void
    {
        foreach ($payload['tweet_create_events'] ?? [] as $tweet) {
            $inReplyTo = $tweet['in_reply_to_status_id_str'] ?? null;

            if (! $inReplyTo) {
                continue;
            }

            $user = $tweet['user'] ?? [];

            $this->executionService->processCommentEvent('x', [
                'account_id'           => $payload['for_user_id'] ?? null,
                'post_id'              => $inReplyTo,
                'comment_id'           => $tweet['id_str'] ?? null,
                'commenter_id'         => $user['id_str'] ?? null,
                'commenter_username'   => $user['screen_name'] ?? null,
                'commenter_name'       => $user['name'] ?? null,
                'text'                 => $tweet['text'] ?? '',
            ]);
        }
    }

    /**
     * Verify the X-Hub-Signature-256 header for Instagram/Facebook webhooks.
     */
    public function verifySignature(Request $request, string $appSecret): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify the CRC token for X/Twitter Account Activity API.
     */
    public function generateXCrcResponse(string $crcToken, string $consumerSecret): string
    {
        $hash = hash_hmac('sha256', $crcToken, $consumerSecret, true);

        return 'sha256=' . base64_encode($hash);
    }
}
