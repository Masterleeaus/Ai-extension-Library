<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Actions;

use App\Domains\WorkCore\System\Actions\Contracts\ReadModelHandlerContract;
use App\Domains\WorkCore\System\Actions\{ReadModelRequest, ReadModelResult};
use App\Domains\WorkCore\System\Modules\Channels\Contracts\ChannelRepositoryContract;

final class SearchChannels implements ReadModelHandlerContract
{
    public function __construct(private ChannelRepositoryContract $repository) {}

    public function handle(ReadModelRequest $request): ReadModelResult
    {
        $filters = $request->payload['filters'] ?? [];
        $perPage = $request->payload['per_page'] ?? 25;

        $result = $this->repository->listChannels($request->companyId, $filters, $perPage);

        return new ReadModelResult(
            data: $result->items(),
            metadata: [
                'total' => $result->total(),
                'per_page' => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
            ],
        );
    }
}
