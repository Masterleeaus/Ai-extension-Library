<?php

declare(strict_types=1);

namespace App\Extensions\FluxPro\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Ai\FalImageWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FalAIWebhookController extends Controller
{
    public function __invoke(Request $request, FalImageWebhookProcessor $processor): JsonResponse
    {
        return $processor->handle($request);
    }
}
