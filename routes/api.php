<?php

use App\Http\Controllers\AskController;
use Illuminate\Support\Facades\Route;

Route::post('/ask', [AskController::class, 'ask'])->middleware('throttle:5,1');