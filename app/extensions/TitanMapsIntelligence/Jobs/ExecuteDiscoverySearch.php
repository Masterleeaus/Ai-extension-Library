<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Jobs;

use App\Extensions\TitanMapsIntelligence\Enums\SearchStatus;
use App\Extensions\TitanMapsIntelligence\Events\SearchStarted;
use App\Extensions\TitanMapsIntelligence\Models\DiscoverySearch;
use App\Extensions\TitanMapsIntelligence\Services\DiscoveryRunService;
use App\Extensions\TitanMapsIntelligence\Providers\PlacesProviderRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ExecuteDiscoverySearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public readonly string $searchId, public readonly string $companyId) {}

    public function handle(DiscoveryRunService $runs, PlacesProviderRegistry $providers, Dispatcher $events): void
    {
        $search = DiscoverySearch::query()->where('company_id', $this->companyId)->whereKey($this->searchId)->firstOrFail();
        if ($search->status === SearchStatus::Cancelled->value || $search->status === SearchStatus::Completed->value) {
            return;
        }

        $providerId = (string) (($search->provider_strategy['provider'] ?? null) ?: config('extensions.titan_maps_intelligence.default_provider', 'google-places'));
        $providers->get($providerId, $this->companyId);
        $run = $runs->begin($this->companyId, $this->searchId, $providerId);
        $search->forceFill(['status' => SearchStatus::Running->value, 'started_at' => $search->started_at ?? now()])->save();
        $events->dispatch(new SearchStarted($this->companyId, $search->requested_by_user_id, conversationId: $search->conversation_id, payload: ['search_id' => $this->searchId, 'run_id' => $run->getKey()]));

        ProcessDiscoveryPage::dispatch($this->searchId, $this->companyId, $run->getKey());
    }
}
