<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult, ActionRequest, PendingDomainEvent};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Channels\Contracts\ChannelRepositoryContract;
use App\Domains\WorkCore\System\References\TypedReference;

final class SyncChannelInventory implements BusinessActionHandlerContract
{
    public function __construct(private ChannelRepositoryContract $repository) {}

    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $channelId = $request->payload['channel_id'] ?? null;
        if (!$channelId) {
            throw new \InvalidArgumentException('Channel ID is required');
        }

        $result = $this->repository->syncInventory($channelId, $request->companyId);

        return new ActionHandlerResult(
            data: $result,
            aggregate: new TypedReference('channel', $channelId),
            events: [new PendingDomainEvent('workcore.channels.inventory.synced', 1, ['result' => $result])],
        );
    }
}
