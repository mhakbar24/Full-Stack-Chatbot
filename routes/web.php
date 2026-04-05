<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerifyController;

Route::get('/verify', [VerifyController::class, 'index']);
Route::get('/check', [VerifyController::class, 'check']);
