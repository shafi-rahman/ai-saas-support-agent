<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DocumentsController;
use App\Http\Controllers\Web\SettingsController;
use Illuminate\Support\Facades\Route;

// ── Guest ─────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/',          fn() => redirect()->route('login'));
    Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',    [AuthController::class, 'login']);
    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/documents',              [DocumentsController::class, 'index'])->name('documents');
    Route::get('/settings',              [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings',             [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/regenerate-key', [SettingsController::class, 'regenerateKey'])->name('settings.regenerate-key');
    Route::get('/chat',    fn() => view('chat'))->name('chat');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
