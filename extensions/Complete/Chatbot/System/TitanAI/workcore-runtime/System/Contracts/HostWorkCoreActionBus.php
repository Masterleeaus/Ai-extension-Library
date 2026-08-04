<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore\Contracts;

use App\Domains\WorkCore\System\Actions\ActionHandlerResult;
use App\Domains\WorkCore\System\Actions\ActionRequest;

/**
 * Stable boundary between the chatbot AI runtime and the host WorkCore domain.
 * The host application should bind this contract to its authoritative command bus.
 */
interface HostWorkCoreActionBus
{
    public function supports(string $action): bool;

    public function dispatch(ActionRequest $request): ActionHandlerResult;
}
