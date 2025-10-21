<?php

namespace App\Http\Controllers;

use App\Models\ProcessVersion;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcessReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version_id' => ['required', 'exists:process_versions,id'],
            'decision' => ['required', 'in:approve,request_changes'],
            'comment' => ['nullable', 'string'],
        ]);

        $version = ProcessVersion::findOrFail($validated['version_id']);
        $this->authorize('review', $version->process);

        $review = Review::create([
            'process_version_id' => $version->id,
            'reviewer_user_id' => $request->user()->id,
            'comment' => $validated['comment'] ?? '',
            'decision' => $validated['decision'],
        ]);

        if ($validated['decision'] === 'approve') {
            $version->update(['status' => 'approved']);
            $version->process->update(['status' => 'in_review']);
        } else {
            $version->update(['status' => 'changes_requested']);
            $version->process->update(['status' => 'draft']);
        }

        return response()->json([
            'review' => $review,
        ], 201);
    }
}
