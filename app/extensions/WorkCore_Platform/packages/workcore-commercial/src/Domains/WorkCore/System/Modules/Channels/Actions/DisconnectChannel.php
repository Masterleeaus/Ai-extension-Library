<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult, ActionRequest, PendingDomainEvent};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Channels\Contracts\ChannelRepositoryContract;
use App\Domains\WorkCore\System\References\TypedReference;

final class DisconnectChannel implements BusinessActionHandlerContract
{
    public function __construct(private ChannelRepositoryContract $repository) {}

    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $channelId = $request->payload['channel_id'] ?? null;
        if (!$channelId) {
            throw new \InvalidArgumentException('Channel ID is required');
        }

        $success = $this->repository->disconnectChannel($channelId, $request->companyId, $request->actorId);

        return new ActionHandlerResult(
            data: ['success' => $success, 'channel_id' => $channelId],
            aggregate: new TypedReference('channel', $channelId),
            events: [new PendingDomainEvent('workcore.channels.channel.disconnected', 1, ['channel_id' => $channelId])],
        );
    }
}
