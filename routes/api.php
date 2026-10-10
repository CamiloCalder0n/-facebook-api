<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V2\PostController as V2PostController;
use Illuminate\Support\Facades\Route;

// Todas las rutas de la API v1
$v1 = function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::get('/posts/{id}/comments', [CommentController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'me']);

        Route::post('/posts', [PostController::class, 'store']);
        Route::delete('/posts/{id}', [PostController::class, 'destroy']);
        Route::post('/posts/{id}/like', [PostController::class, 'like']);
        Route::post('/posts/{id}/comments', [CommentController::class, 'store']);
    });
};

Route::prefix('v1')->group($v1);   // /api/v1/...
Route::group([], $v1);             // /api/...  alias de v1 (mismas URLs de la Parte 1)

// v2: controlador específico con formato nuevo (API Resources)
Route::prefix('v2')->group(function () {
    Route::get('/posts', [V2PostController::class, 'index']);
    Route::get('/posts/{id}', [V2PostController::class, 'show']);
});
