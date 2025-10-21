<?php

namespace App\Http\Controllers;

use App\Services\ExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExtractionController extends Controller
{
    public function __construct(private ExtractionService $service)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string'],
            'level' => ['required', 'in:L1,L2,L3,L4'],
            'domain' => ['nullable', 'string'],
        ]);

        $result = $this->service->extract($validated['text'], $validated['level'], $validated['domain'] ?? null);

        return response()->json($result);
    }
}
