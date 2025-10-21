<?php

namespace App\Http\Controllers;

use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadInitiateController extends Controller
{
    public function __construct(private UploadService $service)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string'],
            'size' => ['required', 'integer', 'min:1'],
            'language' => ['nullable', 'string', 'max:5'],
            'domain' => ['nullable', 'string'],
            'level' => ['nullable', 'string'],
        ]);

        $allowed = array_map('strtolower', config('uploads.allowed_mimes', []));
        if (! in_array(strtolower($validated['mime_type']), $allowed, true)) {
            return response()->json([
                'message' => __('Unsupported media type.'),
            ], 415);
        }

        if ($validated['size'] > config('uploads.max_chunk_bytes')) {
            return response()->json([
                'message' => __('Chunk size exceeds limit of 15 MB.'),
            ], 413);
        }

        $upload = $this->service->initiate($request->user()->id, $validated);

        return response()->json([
            'upload_id' => $upload->id,
        ], 201);
    }
}
