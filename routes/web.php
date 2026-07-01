<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\GithubController;
use App\Http\Controllers\EmailSourceController;
use App\Http\Controllers\LegacyThreadRedirectController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\RssController;
use App\Http\Controllers\RssRfcController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'pages.home');
Volt::route('/top', 'pages.top');
Volt::route('/news', 'pages.news');
Volt::route('/stats', 'pages.stats');

Route::get('/login', GithubController::class)->name('login');
Route::post('/logout', LogoutController::class)->name('logout');

Volt::route('/message/{number}', 'pages.thread')->whereNumber('number');
Route::get('/email/{number}/source', EmailSourceController::class)->whereNumber('number')->middleware('auth');

Route::get('/rss', RssController::class);
Route::get('/rss-rfc', RssRfcController::class);

// Backward compatibility with old thread URLs
Route::get('/thread/{id}', LegacyThreadRedirectController::class)->whereNumber('id');

// Catch-all "not found" page
Route::fallback(fn() => response()->view('errors.404', [], 404));
