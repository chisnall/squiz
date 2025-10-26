<?php

use Chisnall\Squiz\Controllers\SquizController;
use Illuminate\Support\Facades\Route;

Route::post(config('squiz.route_path') . '/clear', [SquizController::class, 'clearLog']);
Route::post(config('squiz.route_path') . '/entries', [SquizController::class, 'getLogEntries']);
Route::get(config('squiz.route_path') . '/ids', [SquizController::class, 'getLogIds']);
