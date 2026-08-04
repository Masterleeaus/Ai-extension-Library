<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Http\Controllers;

use App\Extensions\SocialMediaAutomation\System\Services\WebhookProcessor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookController extends Controller
{
    public function __construct(
        private WebhookProcessor $processor
    ) {}

    /**
     * Handle X/Twitter Account Activity API webhooks.
     *
     * GET = CRC challenge verification
     * POST = Event delivery
     */
    public function x(Request $request): Response
    {
        // GET: CRC challenge response
        if ($request->isMethod('GET')) {
            $crcToken = $request->get('crc_token');

            if (! $crcToken) {
                return response('Missing crc_token', 400);
            }

            $consumerSecret = setting('X_API_SECRET');

            if (! $consumerSecret) {
                return response('Webhook not configured', 500);
            }

            $responseToken = $this->processor->generateXCrcResponse($crcToken, $consumerSecret);

            return response(json_encode(['response_token' => $responseToken]), 200)
                ->header('Content-Type', 'application/json');
        }

        // POST: Process incoming events
        if ($request->isMethod('POST')) {
            try {
                $this->processor->processXPayload($request->json()->all());
            } catch (Throwable $e) {
                Log::error('X webhook processing failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            return response('', 200);
        }

        return response('Method not allowed', 405);
    }

    /**
     * Handle TikTok webhook events.
     *
     * TikTok uses a webhook verification challenge and POST event delivery.
     */
    public function tiktok(Request $request): Response
    {
        // TikTok verification challenge
        if ($request->isMethod('POST') && $request->json('challenge')) {
            return response(json_encode([
                'challenge' => $request->json('challenge'),
            ]), 200)->header('Content-Type', 'application/json');
        }

        // Process comment events
        if ($request->isMethod('POST')) {
            try {
                $event = $request->json('event', '');

                if ($event === 'comment.create') {
                    $data = $request->json('data', []);

                    $this->processor->executionService()->processCommentEvent('tiktok', [
                        'account_id'           => null,
                        'post_id'              => $data['video_id'] ?? null,
                        'comment_id'           => $data['comment_id'] ?? null,
                        'commenter_id'         => $data['user_id'] ?? null,
                        'commenter_username'   => $data['username'] ?? null,
                        'commenter_name'       => $data['display_name'] ?? null,
                        'text'                 => $data['text'] ?? '',
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('TikTok webhook processing failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            return response('', 200);
        }

        return response('Method not allowed', 405);
    }

    /**
     * Handle Facebook webhook events.
     *
     * GET  = Hub verification challenge
     * POST = Event delivery (comment events on page posts)
     */
    public function facebook(Request $request): Response
    {
        // GET: Hub verification challenge
        if ($request->isMethod('GET')) {
            $mode = $request->get('hub_mode') ?? $request->get('hub.mode');
            $token = $request->get('hub_verify_token') ?? $request->get('hub.verify_token');
            $challenge = $request->get('hub_challenge') ?? $request->get('hub.challenge');

            $verifyToken = setting('FACEBOOK_WEBHOOK_VERIFY_TOKEN');

            Log::debug('Facebook webhook GET verification', [
                'mode'                    => $mode,
                'token_match'             => $token === $verifyToken,
                'has_challenge'           => ! empty($challenge),
                'verify_token_configured' => ! empty($verifyToken),
            ]);

            if ($mode === 'subscribe' && $token === $verifyToken) {
                return response((string) $challenge, 200);
            }

            return response('Forbidden', 403);
        }

        // POST: Process incoming events
        if ($request->isMethod('POST')) {
            $appSecret = setting('FACEBOOK_APP_SECRET');

            Log::debug('Facebook webhook POST received', [
                'has_app_secret'  => ! empty($appSecret),
                'has_signature'   => $request->hasHeader('X-Hub-Signature-256'),
                'content_length'  => strlen($request->getContent()),
                'payload_preview' => $request->json()->all(),
            ]);

            if ($appSecret && ! $this->processor->verifySignature($request, $appSecret)) {
                Log::warning('Facebook webhook signature verification failed', [
                    'signature_header' => $request->header('X-Hub-Signature-256'),
                ]);

                return response('Forbidden', 403);
            }

            try {
                $this->processor->processFacebookPayload($request->json()->all());
            } catch (Throwable $e) {
                Log::error('Facebook webhook processing failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return response('', 200);
        }

        return response('Method not allowed', 405);
    }

    /**
     * Handle Instagram webhook events.
     *
     * GET  = Hub verification challenge
     * POST = Event delivery (comment events on media)
     */
    public function instagram(Request $request): Response
    {
        // GET: Hub verification challenge
        if ($request->isMethod('GET')) {
            $mode = $request->get('hub_mode') ?? $request->get('hub.mode');
            $token = $request->get('hub_verify_token') ?? $request->get('hub.verify_token');
            $challenge = $request->get('hub_challenge') ?? $request->get('hub.challenge');

            $verifyToken = setting('FACEBOOK_WEBHOOK_VERIFY_TOKEN');

            if ($mode === 'subscribe' && $token === $verifyToken) {
                return response((string) $challenge, 200);
            }

            return response('Forbidden', 403);
        }

        // POST: Process incoming events
        if ($request->isMethod('POST')) {
            $appSecret = setting('FACEBOOK_APP_SECRET');

            if ($appSecret && ! $this->processor->verifySignature($request, $appSecret)) {
                Log::warning('Instagram webhook signature verification failed');

                return response('Forbidden', 403);
            }

            try {
                $this->processor->processInstagramPayload($request->json()->all());
            } catch (Throwable $e) {
                Log::error('Instagram webhook processing failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            return response('', 200);
        }

        return response('Method not allowed', 405);
    }

    /**
     * Handle LinkedIn webhook events.
     *
     * LinkedIn uses validation endpoint and POST event delivery.
     */
    public function linkedin(Request $request): Response
    {
        // LinkedIn validation challenge
        if ($request->isMethod('GET') && $request->has('validationToken')) {
            return response($request->get('validationToken'), 200)
                ->header('Content-Type', 'text/plain');
        }

        // Process comment events
        if ($request->isMethod('POST')) {
            try {
                $eventType = $request->header('X-LinkedIn-Event-Type', '');

                if (str_contains($eventType, 'COMMENT')) {
                    $data = $request->json()->all();
                    $actor = $data['actor'] ?? '';
                    $object = $data['object'] ?? '';

                    $this->processor->executionService()->processCommentEvent('linkedin', [
                        'account_id'           => null,
                        'post_id'              => $object,
                        'comment_id'           => $data['id'] ?? null,
                        'commenter_id'         => $actor,
                        'commenter_username'   => $data['actor~']['vanityName'] ?? null,
                        'commenter_name'       => ($data['actor~']['localizedFirstName'] ?? '') . ' ' . ($data['actor~']['localizedLastName'] ?? ''),
                        'text'                 => $data['message']['text'] ?? '',
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('LinkedIn webhook processing failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            return response('', 200);
        }

        return response('Method not allowed', 405);
    }
}
