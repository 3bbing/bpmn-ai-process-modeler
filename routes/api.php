<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BpmnGenerationController;
use App\Http\Controllers\ExtractionController;
use App\Http\Controllers\ProcessController;
use App\Http\Controllers\ProcessExportController;
use App\Http\Controllers\ProcessReviewController;
use App\Http\Controllers\ProcessVersionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TranscriptionController;
use App\Http\Controllers\UploadChunkController;
use App\Http\Controllers\UploadFinalizeController;
use App\Http\Controllers\UploadInitiateController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/uploads', UploadInitiateController::class);
    Route::post('/uploads/{upload}/chunks', UploadChunkController::class);
    Route::post('/uploads/{upload}/finalize', UploadFinalizeController::class);

    Route::post('/transcriptions', [TranscriptionController::class, 'store']);
    Route::post('/extract', [ExtractionController::class, 'store']);
    Route::post('/bpmn', [BpmnGenerationController::class, 'store']);

    Route::apiResource('processes', ProcessController::class);
    Route::post('processes/{process}/versions', [ProcessVersionController::class, 'store']);
    Route::patch('process-versions/{version}', [ProcessVersionController::class, 'update']);
    Route::post('process-versions/{version}/publish', [ProcessVersionController::class, 'publish']);

    Route::post('reviews', [ProcessReviewController::class, 'store']);

    Route::get('search', SearchController::class);
    Route::get('processes/{process}/versions/{version}/export', ProcessExportController::class);
});
