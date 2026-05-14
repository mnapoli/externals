<?php

declare(strict_types=1);

use App\Http\Controllers\EmailSourceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyThreadRedirectController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\RssController;
use App\Http\Controllers\RssRfcController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\TopController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);
Route::get('/top', TopController::class);
Route::get('/news', NewsController::class);
Route::get('/stats', StatsController::class);

Route::get('/login', LoginController::class);
Route::get('/logout', LogoutController::class);

Route::get('/message/{number}', ThreadController::class)->whereNumber('number');
Route::get('/email/{number}/source', EmailSourceController::class)->whereNumber('number');
Route::post('/votes/{number}', VoteController::class)->whereNumber('number');

Route::get('/rss', RssController::class);
Route::get('/rss-rfc', RssRfcController::class);

// Backward compatibility with old thread URLs
Route::get('/thread/{id}', LegacyThreadRedirectController::class)->whereNumber('id');

// Catch-all "not found" page
Route::fallback(fn () => response()->view('errors.404', [], 404));
