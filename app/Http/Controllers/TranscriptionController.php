<?php

namespace App\Http\Controllers;

use App\Services\TranscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranscriptionController extends Controller
{
    public function __construct(private TranscriptionService $service)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file_refs' => ['required', 'array', 'min:1'],
            'file_refs.*' => ['required', 'string'],
            'language' => ['nullable', 'string', 'max:5'],
        ]);

        $result = $this->service->transcribe($validated['file_refs'], $validated['language'] ?? null);

        return response()->json($result);
    }
}
