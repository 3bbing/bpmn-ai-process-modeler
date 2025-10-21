<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadFinalizeController extends Controller
{
    public function __construct(private UploadService $service)
    {
    }

    public function __invoke(Request $request, Upload $upload): JsonResponse
    {
        $request->validate([
            'concat' => ['boolean'],
        ]);

        $result = $this->service->finalize($upload, $request->boolean('concat'));

        return response()->json($result);
    }
}
