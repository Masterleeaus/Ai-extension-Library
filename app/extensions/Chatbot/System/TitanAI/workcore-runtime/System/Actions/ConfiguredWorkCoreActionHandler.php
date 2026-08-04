<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore\Actions;

use App\Domains\WorkCore\System\Actions\ActionHandlerResult;
use App\Domains\WorkCore\System\Actions\ActionRequest;
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

/**
 * Delegates a native WorkCore action to the authoritative host-domain handler.
 * No Eloquent fallback or fabricated success response is permitted.
 */
final class ConfiguredWorkCoreActionHandler implements BusinessActionHandlerContract
{
    public function __construct(private readonly Container $container) {}

    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $mapping = (array) config('titan_ai_runtime.host_action_handlers', []);
        $target = $mapping[$request->key] ?? null;

        if (! is_string($target) || trim($target) === '') {
            throw new RuntimeException("No authoritative WorkCore handler is configured for action [{$request->key}].");
        }

        [$service, $method] = array_pad(explode('@', $target, 2), 2, null);
        if (! is_string($service) || $service === '' || ! $this->container->bound($service)) {
            throw new RuntimeException("Configured WorkCore action service [{$service}] is not bound.");
        }

        $handler = $this->container->make($service);
        $method = is_string($method) && $method !== '' ? $method : '__invoke';
        if (! is_callable([$handler, $method])) {
            throw new RuntimeException("Configured WorkCore action handler [{$target}] is not callable.");
        }

        $result = $handler->{$method}($request->payload, $request);
        if ($result instanceof ActionHandlerResult) {
            return $result;
        }
        if (! is_array($result)) {
            throw new RuntimeException("WorkCore action handler [{$target}] must return an array or ActionHandlerResult.");
        }

        return new ActionHandlerResult(
            data: $result,
            events: [],
        );
    }
}
