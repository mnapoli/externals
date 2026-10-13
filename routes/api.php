<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ApiIndexController;
use App\Http\Controllers\Api\ListRfcsController;
use App\Http\Controllers\Api\ListThreadsController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ShowRfcController;
use App\Http\Controllers\Api\ShowThreadController;
use Illuminate\Support\Facades\Route;

Route::get('/', ApiIndexController::class);
Route::get('/threads', ListThreadsController::class);
Route::get('/threads/{number}', ShowThreadController::class)->whereNumber('number');
Route::get('/search', SearchController::class);
Route::get('/rfcs', ListRfcsController::class);
Route::get('/rfcs/{slug}', ShowRfcController::class)->where('slug', '[A-Za-z0-9_.\-/]+');
