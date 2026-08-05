<?php

namespace App\Extensions\AdvancedImage\System\Http\Controllers;

use App\Extensions\AdvancedImage\System\Services\AdvancedFreepikService;
use App\Extensions\AdvancedImage\System\Services\AdvancedNovitaService;
use App\Extensions\AdvancedImage\System\Services\ClipDropService;
use App\Http\Controllers\Controller;
use App\Models\UserOpenai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdvancedImageWebhookController extends Controller
{
    private const MAX_WEBHOOK_AGE_SECONDS = 300;

    private const REPLAY_TTL_SECONDS = 600;

    public function __construct(
        public AdvancedNovitaService $novitaService,
        public ClipDropService $clipDropService,
        public AdvancedFreepikService $freepikService
    ) {}

    public function __invoke(Request $request, string $model = 'freepik'): void
    {
        if ($model === 'novita') {
            abort(403, 'Novita webhooks are disabled until authenticated callbacks can be verified.');
        }

        abort_unless($model === 'freepik', 404);

        $this->assertValidFreepikWebhook($request);

        $taskId = $request->get('request_id') ?: $request->get('task_id');

        abort_if(blank($taskId), 422, 'A provider task identifier is required.');

        /**
         * @var UserOpenai $task
         */
        $task = UserOpenai::query()
            ->where('request_id', $taskId)
            ->where('is_advanced_image', true)
            ->where('payload->model', 'freepik')
            ->firstOrFail();

        $this->freepikService->webhook($task, $request->all());
    }

    private function assertValidFreepikWebhook(Request $request): void
    {
        $webhookId = trim((string) $request->header('webhook-id'));
        $webhookTimestamp = trim((string) $request->header('webhook-timestamp'));
        $signatureHeader = trim((string) $request->header('webhook-signature'));
        $secret = trim((string) (config('services.freepik.webhook_secret') ?: setting('freepik_webhook_secret')));

        abort_if($secret === '', 503, 'Freepik webhook authentication is not configured.');
        abort_if($webhookId === '' || $webhookTimestamp === '' || $signatureHeader === '', 403);
        abort_unless(ctype_digit($webhookTimestamp), 403);

        $sentAt = (int) $webhookTimestamp;
        if ($sentAt > 9_999_999_999) {
            $sentAt = intdiv($sentAt, 1000);
        }

        abort_if(abs(now()->timestamp - $sentAt) > self::MAX_WEBHOOK_AGE_SECONDS, 403);

        $contentToSign = $webhookId . '.' . $webhookTimestamp . '.' . $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $contentToSign, $secret, true));
        $signatureIsValid = false;

        foreach (preg_split('/\s+/', $signatureHeader) ?: [] as $versionedSignature) {
            [$version, $signature] = array_pad(explode(',', $versionedSignature, 2), 2, null);

            if ($version === 'v1' && is_string($signature) && hash_equals($expectedSignature, $signature)) {
                $signatureIsValid = true;
                break;
            }
        }

        abort_unless($signatureIsValid, 403);

        $replayKey = 'advanced-image:freepik-webhook:' . hash('sha256', $webhookId);
        abort_unless(Cache::add($replayKey, true, self::REPLAY_TTL_SECONDS), 409);
    }
}
