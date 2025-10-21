<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadChunkController extends Controller
{
    public function __construct(private UploadService $service)
    {
    }

    public function __invoke(Request $request, Upload $upload): JsonResponse
    {
        $validated = $request->validate([
            'chunk' => ['required', 'file'],
            'idx' => ['required', 'integer', 'min:0'],
            'checksum' => ['required', 'string'],
        ]);

        $file = $validated['chunk'];
        if ($file->getSize() > config('uploads.max_chunk_bytes')) {
            return response()->json([
                'message' => __('Chunk size exceeds limit of 15 MB.'),
            ], 413);
        }

        $result = $this->service->storeChunk(
            $upload,
            $file->getClientOriginalName(),
            file_get_contents($file->getRealPath()),
            $validated['idx'],
            $validated['checksum']
        );

        return response()->json($result, 201);
    }
}
