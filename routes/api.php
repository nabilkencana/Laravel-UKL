<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login-email', 'loginEmail');
    Route::post('request-otp-wa', 'requestOtpWa');
    Route::post('verify-otp-wa', 'verifyOtpWa');
    Route::post('forgot-password/request', 'requestForgotPassword');
    Route::post('forgot-password/reset', 'resetPassword');
});
