<?php

use Chisnall\Squiz\Http\Controllers\SquizController;
use Illuminate\Support\Facades\Route;

Route::get(config('squiz.route_path'), [SquizController::class, 'index'])->name('squizIndex');
