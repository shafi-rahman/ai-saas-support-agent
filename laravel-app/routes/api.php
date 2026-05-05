<?php

use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ConversationController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    try {
        $base = preg_replace('#/api/(chat|generate)$#', '', config('ai.providers.ollama.url'));
        $up   = Http::timeout(5)->get($base)->successful();
    } catch (\Exception) {
        $up = false;
    }

    return response()->json([
        'status' => $up ? 'ok' : 'degraded',
        'ollama' => $up ? 'reachable' : 'unreachable',
        'time'   => now()->toISOString(),
    ], $up ? 200 : 503);
});

Route::prefix('v1')->group(function () {

    // ── Public: auth ──────────────────────────────────────────────────────────
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login',    [AuthController::class, 'login']);

    // ── Authenticated ─────────────────────────────────────────────────────────
    Route::middleware(['auth:api', 'throttle:120,1'])->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me',      [AuthController::class, 'me']);

        // ── Chat (any authenticated user) ─────────────────────────────────────
        Route::post('/chat',        [AIController::class, 'chat']);
        Route::post('/chat/stream', [AIController::class, 'stream']);
        Route::post('/chat/sse',    [AIController::class, 'sse']);

        // ── Admin only ────────────────────────────────────────────────────────
        Route::middleware('role:admin')->group(function () {

            // Knowledge base
            Route::get('/documents',          [DocumentController::class, 'index']);
            Route::post('/documents',         [DocumentController::class, 'store']);
            Route::get('/documents/{id}',     [DocumentController::class, 'show']);
            Route::delete('/documents/{id}',  [DocumentController::class, 'destroy']);

            // Conversation history & logs
            Route::get('/conversations',                  [ConversationController::class, 'index']);
            Route::get('/conversations/{session_id}',     [ConversationController::class, 'show']);
            Route::get('/ai/logs',                        [ConversationController::class, 'logs']);
        });
    });
});
