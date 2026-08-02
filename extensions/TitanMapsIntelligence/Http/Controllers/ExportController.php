<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Http\Controllers;

use App\Extensions\TitanMapsIntelligence\Contracts\AuthorisedCompanyContext;
use App\Extensions\TitanMapsIntelligence\Contracts\PermissionAuthorizer;
use App\Extensions\TitanMapsIntelligence\Http\Requests\ExportSearchRequest;
use App\Extensions\TitanMapsIntelligence\Services\CandidateExportService;
use Illuminate\Http\JsonResponse;

final class ExportController
{
    public function __construct(
        private readonly CandidateExportService $exports,
        private readonly AuthorisedCompanyContext $context,
        private readonly PermissionAuthorizer $authorizer,
    ) {}

    public function store(ExportSearchRequest $request, string $search): JsonResponse
    {
        $data = $request->validated();
        $companyId = $this->context->companyId();
        $this->authorizer->authorize($this->context->userId(), $companyId, 'titan-maps-intelligence.search.export', ['search_id' => $search, 'format' => $data['format'] ?? null]);
        $result = $this->exports->export($search, (string) $data['format'], [
            'candidate_status' => $data['candidate_status'] ?? null,
            'conversation_id' => $data['conversation_id'] ?? null,
            'agent_id' => $data['agent_id'] ?? null,
        ]);

        return response()->json(['ok' => true, 'data' => $result]);
    }
}
