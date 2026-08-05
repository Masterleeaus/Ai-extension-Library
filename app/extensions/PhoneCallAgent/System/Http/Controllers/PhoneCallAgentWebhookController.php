<?php

declare(strict_types=1);

namespace App\Extensions\PhoneCallAgent\System\Http\Controllers;

use App\Extensions\PhoneCallAgent\System\Booking\BookingProviderResolver;
use App\Extensions\PhoneCallAgent\System\Booking\BookingToolHandler;
use App\Extensions\PhoneCallAgent\System\Enums\CallStatusEnum;
use App\Extensions\PhoneCallAgent\System\Models\ExtPhoneCallAgent;
use App\Extensions\PhoneCallAgent\System\Models\ExtPhoneCallAgentCall;
use App\Extensions\PhoneCallAgent\System\Models\ExtPhoneCallAgentTranscript;
use App\Extensions\PhoneCallAgent\System\Services\Providers\TwilioPhoneService;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Twilio\Security\RequestValidator;
use Twilio\TwiML\VoiceResponse;

class PhoneCallAgentWebhookController extends Controller
{
    public function twilioInbound(Request $request): Response
    {
        if (Helper::appIsDemo()) {
            return response($this->buildRejectTwiml('This service is not available in demo mode.'), 200)
                ->header('Content-Type', 'text/xml');
        }

        $calledNumber = $request->input('To');
        $callerNumber = $request->input('From');
        $callSid = $request->input('CallSid');

        Log::info('[Twilio] Inbound call', [
            'to'      => $calledNumber,
            'from'    => $callerNumber,
            'callSid' => $callSid,
        ]);

        // Primary: explicit per-agent number mapping. Fallback: legacy call-history scan.
        $agent = ExtPhoneCallAgent::query()
            ->where('active', true)
            ->where('provider', 'twilio')
            ->where('phone_number', $calledNumber)
            ->first();

        if (! $agent) {
            $agent = ExtPhoneCallAgent::query()
                ->where('active', true)
                ->get()
                ->first(function ($agent) use ($calledNumber) {
                    $phoneNumbers = $agent->calls()
                        ->whereNotNull('called_number')
                        ->pluck('called_number')
                        ->unique();

                    return $phoneNumbers->contains($calledNumber);
                });
        }

        if (! $agent) {
            Log::warning('[Twilio] No agent found for number', ['to' => $calledNumber]);

            return response($this->buildRejectTwiml('This number is not in service.'), 200)
                ->header('Content-Type', 'text/xml');
        }

        if (! $this->verifyTwilioSignature($request, $agent)) {
            Log::warning('[Twilio] Invalid signature', ['to' => $calledNumber]);

            return response($this->buildRejectTwiml('Request could not be verified.'), 403)
                ->header('Content-Type', 'text/xml');
        }

        Log::info('[Twilio] Agent matched', ['agent_id' => $agent->id, 'agent' => $agent->title]);

        // Plan limit enforcement
        $user = $agent->user;
        $limit = (int) ($user->activePlan()?->phone_call_agent_seconds_limit ?? -1);

        if ($limit === 0) {
            return response($this->buildRejectTwiml('Service is not available on your plan.'), 200)
                ->header('Content-Type', 'text/xml');
        }

        if ($limit > 0) {
            $used = ExtPhoneCallAgentCall::query()
                ->where('user_id', $user->id)
                ->whereMonth('started_at', now()->month)
                ->whereYear('started_at', now()->year)
                ->sum('duration');

            if ($used >= $limit) {
                return response($this->buildRejectTwiml('Monthly call limit reached.'), 200)
                    ->header('Content-Type', 'text/xml');
            }
        }

        ExtPhoneCallAgentCall::create([
            'uuid'          => Str::uuid()->toString(),
            'agent_id'      => $agent->id,
            'user_id'       => $user->id,
            'call_sid'      => $callSid,
            'caller_number' => $callerNumber,
            'called_number' => $calledNumber,
            'status'        => CallStatusEnum::Ringing,
            'started_at'    => now(),
        ]);

        if ($agent->provider->value === 'twilio') {
            $twiml = $this->buildConversationRelayTwiml($agent, $request);
            Log::info('[Twilio] Returning ConversationRelay TwiML', ['twiml' => $twiml]);
        } else {
            $agentId = $agent->agent_id;
            $twiml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Connect>
        <Stream url="wss://api.elevenlabs.io/v1/convai/conversation?agent_id={$agentId}" />
    </Connect>
</Response>
XML;
        }

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    public function twilioStatus(Request $request): Response
    {
        $callSid = $request->input('CallSid');
        $callStatus = $request->input('CallStatus');

        Log::info('[Twilio] Status webhook', ['callSid' => $callSid, 'status' => $callStatus]);
        $duration = (int) $request->input('CallDuration', 0);

        $call = ExtPhoneCallAgentCall::query()
            ->where('call_sid', $callSid)
            ->first();

        // SECURITY: Must verify signature regardless of whether call exists
        if ($call && $call->agent) {
            if (! $this->verifyTwilioSignature($request, $call->agent)) {
                Log::warning('[Twilio] Invalid status signature', ['callSid' => $callSid]);
                return response('', 403);
            }
        } else {
            // SECURITY: Still reject unverified orphaned status events
            Log::warning('[Twilio] Status webhook for unknown call', ['callSid' => $callSid]);
            return response('', 204);
        }

        if ($call) {
            $status = match ($callStatus) {
                'in-progress', 'ringing' => CallStatusEnum::Answered,
                'completed'              => CallStatusEnum::Completed,
                'failed', 'busy'         => CallStatusEnum::Failed,
                'no-answer'              => CallStatusEnum::Missed,
                default                  => CallStatusEnum::Completed,
            };

            $call->update([
                'status'   => $status,
                'duration' => $duration,
                'ended_at' => $callStatus === 'completed' ? now() : null,
            ]);
        }

        return response('', 204);
    }

    public function elevenLabsInbound(Request $request): Response
    {
        if (Helper::appIsDemo()) {
            return response('', 204);
        }

        if (! $this->verifyElevenLabsSignature($request)) {
            return response('Unauthorized', 401);
        }

        $payload = $request->all();
        $type = $payload['type'] ?? null;

        // ElevenLabs post-call webhook: single payload after call ends
        if ($type === 'post_call_transcription') {
            $data = $payload['data'] ?? [];
            $conversationId = $data['conversation_id'] ?? null;
            $agentId = $data['agent_id'] ?? null;
            $transcript = $data['transcript'] ?? [];
            $duration = (int) ($data['metadata']['call_duration_secs'] ?? $data['duration_seconds'] ?? 0);

            if (! $conversationId || ! $agentId) {
                return response('', 204);
            }

            $agent = ExtPhoneCallAgent::query()
                ->where('agent_id', $agentId)
                ->first();

            if (! $agent) {
                return response('', 204);
            }

            $call = ExtPhoneCallAgentCall::query()
                ->where('call_sid', $conversationId)
                ->first();

            if (! $call) {
                $call = ExtPhoneCallAgentCall::create([
                    'uuid'       => Str::uuid()->toString(),
                    'agent_id'   => $agent->id,
                    'user_id'    => $agent->user_id,
                    'call_sid'   => $conversationId,
                    'status'     => CallStatusEnum::Completed,
                    'duration'   => $duration,
                    'started_at' => now()->subSeconds($duration),
                    'ended_at'   => now(),
                ]);
            } else {
                $call->update([
                    'status'   => CallStatusEnum::Completed,
                    'duration' => $duration,
                    'ended_at' => now(),
                ]);
            }

            foreach ($transcript as $turn) {
                $message = $turn['message'] ?? '';
                $role = $turn['role'] ?? 'user';

                if ($message) {
                    ExtPhoneCallAgentTranscript::create([
                        'call_id' => $call->id,
                        'role'    => $role,
                        'message' => $message,
                    ]);
                }
            }

            return response('', 204);
        }

        // Legacy / real-time streaming event types (kept for forward-compat)
        $conversationId = $payload['conversation_id'] ?? null;
        $agentId = $payload['agent_id'] ?? null;

        if ($type === 'conversation_initiation_metadata' && $conversationId && $agentId) {
            $agent = ExtPhoneCallAgent::query()
                ->where('agent_id', $agentId)
                ->first();

            if ($agent) {
                ExtPhoneCallAgentCall::create([
                    'uuid'       => Str::uuid()->toString(),
                    'agent_id'   => $agent->id,
                    'user_id'    => $agent->user_id,
                    'call_sid'   => $conversationId,
                    'status'     => CallStatusEnum::Answered,
                    'started_at' => now(),
                ]);
            }
        }

        if ($type === 'transcript' && $conversationId) {
            $call = ExtPhoneCallAgentCall::query()
                ->where('call_sid', $conversationId)
                ->first();

            if ($call) {
                $role = $payload['role'] ?? 'user';
                $message = $payload['message'] ?? '';

                if ($message) {
                    ExtPhoneCallAgentTranscript::create([
                        'call_id' => $call->id,
                        'role'    => $role,
                        'message' => $message,
                    ]);
                }
            }
        }

        if ($type === 'conversation_ended' && $conversationId) {
            $call = ExtPhoneCallAgentCall::query()
                ->where('call_sid', $conversationId)
                ->first();

            if ($call) {
                $call->update([
                    'status'   => CallStatusEnum::Completed,
                    'duration' => $payload['duration_seconds'] ?? 0,
                    'ended_at' => now(),
                ]);
            }
        }

        return response('', 204);
    }

    public function elevenLabsTool(Request $request, string $agentUuid, string $toolName): JsonResponse
    {
        // SECURITY: Verify tool callback signature before processing
        if (! $this->verifyElevenLabsSignature($request)) {
            Log::warning('[ElevenLabs Tool] Rejected: invalid signature', ['tool' => $toolName]);
            return response()->json(
                ['error' => 'Unauthorized'],
                401
            );
        }

        // SECURITY: Verify agent exists and is active with booking enabled
        $agent = ExtPhoneCallAgent::query()
            ->where('uuid', $agentUuid)
            ->where('active', true)
            ->first();

        if (! $agent) {
            Log::warning('[ElevenLabs Tool] Rejected: agent not found', ['uuid' => $agentUuid]);
            return response()->json(
                ['error' => 'Agent not found'],
                404
            );
        }

        if (! $agent->booking_enabled) {
            Log::warning('[ElevenLabs Tool] Rejected: booking not enabled', ['agent_id' => $agent->id]);
            return response()->json(
                ['error' => 'Booking not configured'],
                400
            );
        }

        // SECURITY: Log tool name and sanitized parameters only (no raw body)
        Log::info('[ElevenLabs Tool] Processing', [
            'agent_id' => $agent->id,
            'tool' => $toolName,
            'param_count' => count($request->all()),
        ]);

        $parameters = $request->has('parameters')
            ? $request->input('parameters', [])
            : $request->all();

        try {
            $provider = app(BookingProviderResolver::class)->resolve($agent);
            $handler = new BookingToolHandler($provider);
            $result = $handler->handle($toolName, $parameters);

            Log::info('[ElevenLabs Tool] Success', [
                'agent_id' => $agent->id,
                'tool' => $toolName,
            ]);
        } catch (Throwable $e) {
            // SECURITY: Do not leak internal exception messages
            Log::error('[ElevenLabs Tool] Error', [
                'agent_id' => $agent->id,
                'tool' => $toolName,
                'error_class' => get_class($e),
            ]);
            $result = 'Booking action could not be completed';
        }

        return response()->json(['result' => $result]);
    }

    /**
     * Validate the X-Twilio-Signature using the resolved agent's auth token.
     * SECURITY: Fails closed when verification credentials are missing or invalid.
     */
    private function verifyTwilioSignature(Request $request, ExtPhoneCallAgent $agent): bool
    {
        $token = $agent->twilio_auth_token;

        // CRITICAL SECURITY: Require active credentials - fail closed when missing
        if (empty($token)) {
            Log::error('[Twilio] Webhook rejected: no auth token configured for agent', [
                'agent_id' => $agent->id,
                'call_sid' => $request->input('CallSid'),
            ]);
            return false;
        }

        $signature = $request->header('X-Twilio-Signature', '');

        if (empty($signature)) {
            Log::warning('[Twilio] Webhook rejected: missing X-Twilio-Signature header', [
                'agent_id' => $agent->id,
                'call_sid' => $request->input('CallSid'),
            ]);
            return false;
        }

        $isValid = (new RequestValidator($token))->validate($signature, $request->fullUrl(), $request->post());

        if (!$isValid) {
            Log::warning('[Twilio] Webhook rejected: invalid signature', [
                'agent_id' => $agent->id,
                'call_sid' => $request->input('CallSid'),
            ]);
        }

        return $isValid;
    }

    private function verifyElevenLabsSignature(Request $request): bool
    {
        $secret = setting('elevenlabs_webhook_secret');

        // CRITICAL SECURITY: Require active credentials - fail closed when missing
        if (empty($secret)) {
            Log::error('[ElevenLabs] Webhook rejected: no webhook secret configured');
            return false;
        }

        $header = $request->header('ElevenLabs-Signature', '');
        $body = $request->getContent();

        if (empty($header)) {
            Log::warning('[ElevenLabs] Webhook rejected: missing ElevenLabs-Signature header');
            return false;
        }

        // header format: t=<timestamp>,v0=<hex_signature>
        preg_match('/t=(\d+)/', $header, $tMatch);
        preg_match('/v0=([a-f0-9]+)/', $header, $vMatch);

        if (empty($tMatch[1]) || empty($vMatch[1])) {
            Log::warning('[ElevenLabs] Webhook rejected: invalid signature header format');
            return false;
        }

        $timestamp = (int) $tMatch[1];
        $receivedSig = $vMatch[1];

        // SECURITY: Enforce timestamp freshness (5 minute window)
        $now = (int) (microtime(true) * 1000); // Current time in milliseconds
        $age = $now - $timestamp;
        $maxAge = 5 * 60 * 1000; // 5 minutes in milliseconds

        if ($age < 0 || $age > $maxAge) {
            Log::warning('[ElevenLabs] Webhook rejected: timestamp outside freshness window', [
                'age_ms' => $age,
                'max_age_ms' => $maxAge,
            ]);
            return false;
        }

        // ElevenLabs signs: HMAC-SHA256(secret, "{timestamp}.{body}")
        $expectedWithDot = hash_hmac('sha256', "{$timestamp}.{$body}", $secret);
        // Fallback: some versions sign without dot separator
        $expectedNoDot = hash_hmac('sha256', "{$timestamp}{$body}", $secret);

        if (! hash_equals($expectedWithDot, $receivedSig) && ! hash_equals($expectedNoDot, $receivedSig)) {
            Log::warning('[ElevenLabs] Webhook rejected: invalid signature');
            return false;
        }

        return true;
    }

    private function buildConversationRelayTwiml(ExtPhoneCallAgent $agent, Request $request): string
    {
        $host = $request->header('X-Forwarded-Host') ?: $request->getHost();
        $wsUrl = 'wss://' . $host;
        $wsUrl .= '/api/phone-call-agent/ws/twilio/' . $agent->uuid;

        $attrs = array_merge(
            ['url' => $wsUrl],
            TwilioPhoneService::conversationRelayVoice($agent->voice_id ?: 'Polly.Joanna-Neural'),
        );

        if ($agent->welcome_message) {
            $attrs['welcomeGreeting'] = $agent->welcome_message;
        }

        if ($agent->language && $agent->language !== 'auto') {
            $attrs['language'] = $agent->language;
        }

        $response = new VoiceResponse;
        $response->connect()->conversationRelay($attrs);

        return (string) $response;
    }

    private function buildRejectTwiml(string $message): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Say>{$message}</Say>
    <Hangup />
</Response>
XML;
    }
}
