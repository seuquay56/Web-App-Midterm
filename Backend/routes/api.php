<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NoteController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Health endpoint
Route::get('/health', function () {
    try {
        // Establish the current PDO connection
        DB::connection()->getPdo();
        $db = 'ok'; 
    } catch (\Throwable $e) {
        $db = 'down';
    }

    return response() -> json(
        // Reports ok or down
        // Shows the current running container id with gethostname()
    ['db' => $db, 'container' => gethostname()],
    $db === 'ok' ? 200 : 503
    );
});

// Register endpoint
Route::post('/register', [AuthController::class, 'register']);

// Read endpoint
Route::get('/notes', [NoteController::class, 'index'])->middleware('auth:sanctum');

// Update endpoint
Route::put('/notes/{id}', [NoteController::class, 'update'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notes', [NoteController::class, 'index']);
    Route::post('/notes', [NoteController::class, 'store']);
    Route::put('/notes/{id}', [NoteController::class, 'update']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notes', [NoteController::class, 'index']);
    Route::post('/notes', [NoteController::class, 'store']);
    Route::put('/notes/{id}', [NoteController::class, 'update']);
    Route::delete('/notes/{id}', [NoteController::class, 'destroy']);
});