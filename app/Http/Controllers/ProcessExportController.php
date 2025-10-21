<?php

namespace App\Http\Controllers;

use App\Models\Process;
use App\Models\ProcessVersion;
use App\Services\ProcessBookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcessExportController extends Controller
{
    public function __construct(private ProcessBookService $service)
    {
    }

    public function __invoke(Request $request, Process $process, ProcessVersion $version): JsonResponse
    {
        $this->authorize('view', $process);

        abort_unless($version->process_id === $process->id, 404);

        $format = $request->query('fmt', 'bpmn');
        $path = $this->service->export($process, $version, $format);

        return response()->json([
            'path' => $path,
        ]);
    }
}
