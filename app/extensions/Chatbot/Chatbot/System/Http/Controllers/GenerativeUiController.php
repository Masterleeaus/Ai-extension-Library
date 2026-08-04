<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers;

use App\Extensions\Chatbot\System\GenerativeUI\BuilderRegistry;
use App\Extensions\Chatbot\System\GenerativeUI\GenerativeUiSpecNormaliser;
use App\Extensions\Chatbot\System\GenerativeUI\GenerativeUiSpecValidator;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class GenerativeUiController extends Controller
{
    public function __construct(
        private readonly BuilderRegistry $registry,
        private readonly GenerativeUiSpecNormaliser $normaliser,
        private readonly GenerativeUiSpecValidator $validator,
    ) {
    }

    public function catalogue(): JsonResponse
    {
        return response()->json($this->registry->generativeUiCatalogue());
    }

    public function validateSpec(Request $request): JsonResponse
    {
        $payload = $request->validate(['spec' => ['required', 'array']]);
        $result = $this->validator->validate($payload['spec']);
        return response()->json($result, $result['valid'] ? 200 : 422);
    }

    public function repairSpec(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'spec' => ['required', 'array'],
            'surface' => ['nullable', 'string', 'in:chat,builder,page,canvas,mobile,preview'],
        ]);
        $repair = $this->normaliser->repair($payload['spec'], $payload['surface'] ?? 'chat');
        $validation = $this->validator->validate($repair['spec']);
        return response()->json([...$repair, 'validation' => $validation], $validation['valid'] ? 200 : 422);
    }

    public function example(string $slug): JsonResponse
    {
        $spec = $this->registry->find('specs', $slug);
        abort_if($spec === null, 404);
        $normalised = $this->normaliser->normalise($spec, $spec['surface'] ?? 'chat');
        return response()->json(['spec' => $normalised, 'validation' => $this->validator->validate($normalised)]);
    }

    public function lab(Request $request): View
    {
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) $request->query('spec', 'adaptive-operations-briefing')));
        $spec = $this->registry->find('specs', $slug) ?? $this->registry->find('specs', 'adaptive-operations-briefing') ?? [];
        return view('chatbot::home.generative-ui-lab', [
            'catalogue' => $this->registry->generativeUiCatalogue(),
            'initialSpec' => $this->normaliser->normalise($spec, $spec['surface'] ?? 'chat'),
        ]);
    }
}
