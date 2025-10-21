<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessStoreRequest;
use App\Http\Requests\ProcessUpdateRequest;
use App\Http\Resources\ProcessResource;
use App\Models\Process;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProcessController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Process::class);

        $processes = Process::with(['domain', 'owner'])->paginate(15);

        return ProcessResource::collection($processes);
    }

    public function store(ProcessStoreRequest $request): ProcessResource
    {
        $process = Process::create($request->validated());

        return new ProcessResource($process->load(['domain', 'owner']));
    }

    public function show(Process $process): ProcessResource
    {
        $this->authorize('view', $process);

        return new ProcessResource($process->load(['domain', 'owner', 'versions']));
    }

    public function update(ProcessUpdateRequest $request, Process $process): ProcessResource
    {
        $this->authorize('update', $process);

        $process->update($request->validated());

        return new ProcessResource($process->fresh(['domain', 'owner']));
    }

    public function destroy(Process $process): JsonResponse
    {
        $this->authorize('delete', $process);

        $process->delete();

        return response()->noContent();
    }
}
