<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\GithubController;
use App\Http\Controllers\EmailSourceController;
use App\Http\Controllers\LegacyThreadRedirectController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\RssController;
use App\Http\Controllers\RssRfcController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SearchMarkdownController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ThreadMarkdownController;
use App\Http\Middleware\ServeThreadMarkdown;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'pages.home');
Volt::route('/top', 'pages.top');
Volt::route('/news', 'pages.news');
Volt::route('/stats', 'pages.stats');

Route::get('/login', GithubController::class)->name('login');
Route::post('/logout', LogoutController::class)->name('logout');

Volt::route('/message/{number}', 'pages.thread')->whereNumber('number')->middleware(ServeThreadMarkdown::class);
Route::get('/message/{number}.md', ThreadMarkdownController::class)->whereNumber('number')->withoutMiddleware('web');
Route::get('/email/{number}/source', EmailSourceController::class)->whereNumber('number')->middleware('auth');

Route::get('/search', SearchController::class);
Route::get('/search.md', SearchMarkdownController::class)->withoutMiddleware('web');

Route::get('/rss', RssController::class);
Route::get('/rss-rfc', RssRfcController::class);

Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemaps/pages.xml', [SitemapController::class, 'pages']);
Route::get('/sitemaps/threads-{page}.xml', [SitemapController::class, 'threads'])->whereNumber('page');

// Backward compatibility with old thread URLs
Route::get('/thread/{id}', LegacyThreadRedirectController::class)->whereNumber('id');

// Catch-all "not found" page
// abort() lets the exception handler render JSON for /api/* and HTML otherwise
Route::fallback(fn() => abort(404));
