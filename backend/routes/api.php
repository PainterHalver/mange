<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MangaController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/health_check', function () {
    return response()->json(['message' => 'OK'], 200);
});

Route::get('/list-truyen', function () {
    return \Illuminate\Support\Facades\Storage::disk('ftp')->directories();
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('activated');
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::middleware('auth:sanctum')->delete('/logout', [AuthController::class, 'logout']);
});

Route::prefix('mangas')->group(function () {
    Route::get('/', [MangaController::class, 'index']);
    Route::get('/{manga_id}', [MangaController::class, 'show']);
    Route::middleware('auth:sanctum')->post('/bookmark/{manga_id}', [MangaController::class, 'bookmarkToggle']);
    Route::middleware('auth:sanctum')->post('{manga_id}/comment', [CommentController::class, 'create']);
    Route::get('{manga_id}/comments', [CommentController::class, 'getAllComment']);
    Route::middleware('auth:sanctum')->post('{manga_id}/chapter', [ChapterController::class, 'create']);
});

Route::prefix('chapters')->group(function () {
    Route::get('/{chapter_id}', [ChapterController::class, 'show']);
    Route::middleware('auth:sanctum')->post('{chapter_id}/comment', [CommentController::class, 'create']);
    Route::get('{chapter_id}/comments', [CommentController::class, 'getAllComment']);
});

Route::prefix('user')->group(function () {
    Route::post('/request_reset_password', [UserController::class, 'requestResetPassword']);
});

Route::prefix('me')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserController::class, 'me']);
    Route::patch('/', [UserController::class, 'patchMe']);
    Route::post('/avatar', [UserController::class, 'updateMyAvatar']);
});

Route::prefix('users')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{user_id}', [UserController::class, 'show']);
    Route::patch('/{user_id}', [UserController::class, 'patch']);
    Route::post('/{user_id}/avatar', [UserController::class, 'updateUserAvatar']);
});
