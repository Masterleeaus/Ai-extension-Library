<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers\Api\TitanAI;

use App\Extensions\Chatbot\System\TitanAI\Architecture\ProjectArchitectureAuditService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class ProjectArchitectureDiagnosticsController extends Controller
{
    public function __invoke(ProjectArchitectureAuditService $audit): JsonResponse
    {
        return response()->json(['data' => $audit->report()]);
    }
}
