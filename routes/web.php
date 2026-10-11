<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\LiveSecurityStreamController;
use App\Http\Controllers\QuarantineController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SecureVault Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard or login
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated User Routes with Tenant Scoping
Route::middleware(['auth', 'tenant.scope'])->group(function () {
    Route::get('/dashboard', [FileController::class, 'index'])->name('dashboard');

    // File Operations
    Route::prefix('files')->name('files.')->group(function () {
        Route::post('/upload', [FileController::class, 'upload'])->name('upload');
        Route::get('/{file}/download', [FileController::class, 'download'])->name('download');
        Route::get('/{file}/verify-hash', [FileController::class, 'verifyHash'])->name('verify-hash');
        Route::post('/{file}/rename', [FileController::class, 'rename'])->name('rename');
        Route::delete('/{file}', [FileController::class, 'destroy'])->name('destroy');
        Route::post('/{file}/share', [FileController::class, 'share'])->name('share');
    });

    // Folder Operations
    Route::prefix('folders')->name('folders.')->group(function () {
        Route::post('/store', [FolderController::class, 'store'])->name('store');
        Route::delete('/{folder}', [FolderController::class, 'destroy'])->name('destroy');
    });

    // Administrator Security Dashboard & Quarantine Center
    Route::middleware('rbac:tenant_admin,super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/quarantine', [QuarantineController::class, 'index'])->name('quarantine');
        Route::post('/quarantine/{quarantine}/restore', [QuarantineController::class, 'restore'])->name('quarantine.restore');
        Route::delete('/quarantine/{quarantine}/purge', [QuarantineController::class, 'purge'])->name('quarantine.purge');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');
        Route::get('/live-stream', [LiveSecurityStreamController::class, 'stream'])->name('live-stream');
    });
});
