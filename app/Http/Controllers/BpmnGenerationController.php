<?php

namespace App\Http\Controllers;

use App\Services\BpmnGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BpmnGenerationController extends Controller
{
    public function __construct(private BpmnGenerationService $service)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model' => ['required', 'array'],
            'title' => ['nullable', 'string'],
        ]);

        $xml = $this->service->generate($validated['model'], $validated['title'] ?? null);

        return response()->json([
            'bpmn_xml' => $xml,
        ]);
    }
}
