<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Api-Only backend: all endpoints are in routes/api.php
// Route::get('/', function () {
//     return view('welcome');
// });

// Hien thi giao dien home
Route::redirect('/', '/home');
Route::get('/home', function() {
    return view('home');
})->middleware('auth:sanctum');

// Hien thi giao dien register
Route::get('/register', [AuthController::class, 'show_register'])->name('register');

// Hien thi giao dien login
Route::get('/login', [AuthController::class, 'show_login'])->name('login');