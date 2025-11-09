<?php

use Chisnall\Squiz\Http\Controllers\SquizController;
use Illuminate\Support\Facades\Route;

Route::middleware(['squizToken'])->group(function () {
    Route::post(config('squiz.route_path').'/clear', [SquizController::class, 'clearLog'])->name('squizClear');
    Route::post(config('squiz.route_path').'/delete', [SquizController::class, 'deleteEntry'])->name('squizDelete');
    Route::post(config('squiz.route_path').'/entries', [SquizController::class, 'getLogEntries'])->name('squizEntries');
    Route::get(config('squiz.route_path').'/ids', [SquizController::class, 'getLogIds'])->name('squizIds');
});
