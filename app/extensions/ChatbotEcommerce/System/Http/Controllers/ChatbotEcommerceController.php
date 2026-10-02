<?php

namespace App\Extensions\ChatbotEcommerce\System\Http\Controllers;

use App\Extensions\Chatbot\System\Services\ChatbotService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatbotEcommerceController extends Controller
{
    public function __construct(public ChatbotService $service) {}

    public function index(Request $request, CommerceDashboardRuntime $dashboard)
    {
        $agentOptions = $request->user()
            ->externalChatbots()
            ->select(['id', 'uuid', 'title'])
            ->orderBy('title')
            ->get();

        $dashboardSummaries = $agentOptions->mapWithKeys(fn ($agent): array => [\n            (string) $agent->uuid => $dashboard->summary($agent),\n        ]);\n\n        return view('chatbot-ecommerce::index', compact('agentOptions', 'dashboardSummaries'));
    }
}
