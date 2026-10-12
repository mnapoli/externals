<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ListThreadsController;
use App\Http\Controllers\Api\ShowThreadController;
use Illuminate\Support\Facades\Route;

Route::get('/threads', ListThreadsController::class);
Route::get('/threads/{number}', ShowThreadController::class)->whereNumber('number');
