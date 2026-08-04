<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Tools;

use App\Extensions\TitanMapsIntelligence\Contracts\AuthorisedCompanyContext;
use App\Extensions\TitanMapsIntelligence\Contracts\PermissionAuthorizer;
use App\Extensions\TitanMapsIntelligence\DTO\Coordinates;
use App\Extensions\TitanMapsIntelligence\DTO\PlaceSearchRequest;
use App\Extensions\TitanMapsIntelligence\Services\DiscoverySearchService;

final class SearchBusinessesTool
{
    public function __construct(private readonly AuthorisedCompanyContext $context, private readonly PermissionAuthorizer $authorizer, private readonly DiscoverySearchService $searches) {}
    public function execute(array $input): array
    {
        $companyId = $this->context->companyId();
        $this->authorizer->authorize($this->context->userId(), $companyId, 'titan-maps-intelligence.search.create');
        $coordinates = isset($input['latitude'], $input['longitude']) ? new Coordinates((float) $input['latitude'], (float) $input['longitude']) : null;
        $search = $this->searches->create(new PlaceSearchRequest(
            query: (string) ($input['query'] ?? ''), maximumResults: (int) ($input['maximum_results'] ?? 20),
            languageCode: (string) ($input['language'] ?? 'en'), regionCode: $input['country'] ?? null,
            locationBias: $coordinates, radiusMetres: isset($input['radius_metres']) ? (float) $input['radius_metres'] : null,
            includedType: $input['category'] ?? null, openNow: (bool) ($input['open_now'] ?? false),
            minimumRating: isset($input['minimum_rating']) ? (float) $input['minimum_rating'] : null,
        ), ['purpose' => $input['purpose'] ?? 'provider_discovery', 'conversation_id' => $input['conversation_id'] ?? null, 'agent_id' => $input['agent_id'] ?? null, 'correlation_id' => $input['correlation_id'] ?? null]);
        return ['ok' => true, 'data' => ['search_id' => $search->getKey(), 'status' => $search->status]];
    }
}
