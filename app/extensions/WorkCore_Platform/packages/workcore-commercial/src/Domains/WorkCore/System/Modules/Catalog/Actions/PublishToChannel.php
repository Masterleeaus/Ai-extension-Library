<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Catalog\Actions;

use App\Domains\WorkCore\System\Actions\{ActionHandlerResult, ActionRequest, PendingDomainEvent};
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Modules\Catalog\Contracts\CatalogRepositoryContract;
use App\Domains\WorkCore\System\References\TypedReference;

final class PublishToChannel implements BusinessActionHandlerContract
{
    public function __construct(private CatalogRepositoryContract $repository) {}

    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $productPublicId = (string) $request->payload['product_public_id'];
        $channelName = (string) $request->payload['channel_name'];
        $record = $this->repository->publishToChannel($productPublicId, $channelName, $request->payload, $request->companyId);

        return new ActionHandlerResult(
            data: $record,
            aggregate: new TypedReference('channel_publishing', (string) $record['public_id']),
            events: [new PendingDomainEvent('workcore.catalog.product.published_to_channel', 1, [
                'product_id' => $productPublicId,
                'channel' => $channelName,
                'record' => $record,
            ])],
        );
    }
}
