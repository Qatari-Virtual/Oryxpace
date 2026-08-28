<?php

use App\Http\Controllers\Api\PipelineController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\AuthenticatePilotToken;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware([AuthenticatePilotToken::class])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

Route::middleware([ApiKeyMiddleware::class])->group(function () {
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);

    Route::post('/pipelines/application', [PipelineController::class, 'handleApplication']);
    Route::post('/pipelines/recruitment', [PipelineController::class, 'handleRecruitment']);
    Route::post('/pipelines/written-test', [PipelineController::class, 'handleWrittenTest']);
    Route::post('/pipelines/replay-review', [PipelineController::class, 'handleReplayReview']);
    Route::post('/pipelines/discord-ticket', [PipelineController::class, 'handleDiscordTicket']);
    Route::post('/pipelines/pirep-review', [PipelineController::class, 'handlePirepReview']);
});
