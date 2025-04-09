<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Payment\PaystackController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', [AuthController::class, 'Unauthorized']);
Route::post('/webhook/verify',[PaystackController::class, 'processWebhook']);