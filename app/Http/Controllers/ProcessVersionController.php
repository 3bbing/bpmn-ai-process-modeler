<?php

namespace App\Http\Controllers;

use App\Models\Process;
use App\Models\ProcessVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcessVersionController extends Controller
{
    public function store(Request $request, Process $process): JsonResponse
    {
        $this->authorize('update', $process);

        $validated = $request->validate([
            'bpmn_xml' => ['required', 'string'],
            'sop_md' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
            'auto_increment_version' => ['boolean'],
            'version' => ['nullable', 'integer', 'min:1'],
        ]);

        $latestVersion = (int) $process->versions()->max('version');
        $versionNumber = $validated['auto_increment_version']
            ? $latestVersion + 1
            : ($validated['version'] ?? max($latestVersion, 0) + 1);

        $version = $process->versions()->create([
            'version' => $versionNumber,
            'bpmn_xml' => $validated['bpmn_xml'],
            'sop_md' => $validated['sop_md'] ?? '',
            'meta' => $validated['meta'] ?? [],
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        $process->update(['status' => 'draft']);

        return response()->json([
            'version' => $version->fresh(),
        ], 201);
    }

    public function update(Request $request, ProcessVersion $version): JsonResponse
    {
        $this->authorize('update', $version->process);

        $validated = $request->validate([
            'bpmn_xml' => ['sometimes', 'string'],
            'sop_md' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
            'status' => ['nullable', 'in:draft,in_review,published'],
        ]);

        $version->update($validated);

        return response()->json([
            'version' => $version->fresh(),
        ]);
    }

    public function publish(Request $request, ProcessVersion $version): JsonResponse
    {
        $this->authorize('publish', $version->process);

        if (! $this->passesL4Completeness($version)) {
            return response()->json([
                'message' => __('Each task requires a SOP section before publishing.'),
            ], 422);
        }

        $version->process->versions()->update(['is_published' => false]);

        $version->update([
            'is_published' => true,
            'status' => 'published',
        ]);

        $version->process->update(['status' => 'published']);

        return response()->json([
            'version' => $version->fresh(),
        ]);
    }

    protected function passesL4Completeness(ProcessVersion $version): bool
    {
        if ($version->process->level !== 'L4') {
            return true;
        }

        $meta = $version->meta ?? [];
        $tasks = data_get($meta, 'tasks', []);
        if (empty($tasks)) {
            return false;
        }
        foreach ($tasks as $task) {
            if (empty(data_get($task, 'sop.steps'))) {
                return false;
            }
        }

        return true;
    }
}
