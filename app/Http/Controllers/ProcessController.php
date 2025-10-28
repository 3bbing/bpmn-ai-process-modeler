<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessGenerateRequest;
use App\Http\Requests\ProcessStoreRequest;
use App\Http\Requests\ProcessUpdateRequest;
use App\Http\Resources\ProcessResource;
use App\Models\Process;
use App\Models\Domain;
use Illuminate\Support\Str;
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

    public function storeGenerated(ProcessGenerateRequest $request): ProcessResource
    {
        $user = $request->user();

        $domainId = $this->resolveDomainId($request->input('domain_id'), $user->id);

        $process = Process::create([
            'domain_id' => $domainId,
            'code' => $this->generateCode(),
            'title' => $request->validated()['title'],
            'level' => $request->validated()['level'],
            'owner_user_id' => $user->id,
            'status' => 'draft',
            'summary' => $request->validated()['summary'] ?? null,
            'meta' => array_filter([
                'source' => 'capture',
                'transcript' => $request->validated()['transcript'] ?? null,
            ]),
        ]);

        $version = $process->versions()->create([
            'version' => 1,
            'bpmn_xml' => $request->validated()['bpmn_xml'],
            'sop_md' => '',
            'meta' => array_filter([
                'extraction' => $request->validated()['extraction'] ?? null,
            ]),
            'created_by' => $user->id,
            'status' => 'draft',
        ]);

        $process->setRelation('versions', collect([$version]));

        return new ProcessResource($process->load(['domain', 'owner', 'versions']));
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

    protected function resolveDomainId(?int $domainId, int $userId): int
    {
        if ($domainId) {
            return $domainId;
        }

        $domain = Domain::firstOrCreate(
            ['owner_user_id' => $userId, 'name' => 'Default'],
            ['kpis' => null, 'meta' => null]
        );

        return $domain->id;
    }

    protected function generateCode(): string
    {
        do {
            $code = 'P-' . Str::upper(Str::random(6));
        } while (Process::where('code', $code)->exists());

        return $code;
    }
}
