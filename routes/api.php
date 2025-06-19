<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
    Route::get('/permissions', [AuthController::class, 'permissions'])->name('permissions');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});
