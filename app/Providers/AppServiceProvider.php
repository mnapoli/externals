<?php

declare(strict_types=1);

namespace App\Providers;

use Algolia\AlgoliaSearch\Api\SearchClient;
use App\Services\Rss\RssBuilder;
use App\Services\Rss\RssRfcBuilder;
use App\Services\Search\AlgoliaSearchIndex;
use App\Services\Search\ReadOnlySearchIndex;
use App\Services\Search\SearchIndex;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use League\CommonMark\CommonMarkConverter;
use VStelmakh\UrlHighlight\Highlighter\HtmlHighlighter;
use VStelmakh\UrlHighlight\UrlHighlight;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CommonMarkConverter::class, fn () => new CommonMarkConverter([
            'renderer' => [
                'soft_break' => " <br>\n",
            ],
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]));

        $this->app->singleton(UrlHighlight::class, function () {
            $highlighter = new HtmlHighlighter('http', [
                'rel' => 'nofollow',
                'target' => '_blank',
            ]);

            return new UrlHighlight(null, $highlighter);
        });

        $this->app->singleton(SearchClient::class, fn () => SearchClient::create(
            (string) config('services.algolia.app_id'),
            (string) config('services.algolia.api_key'),
        ));

        $this->app->singleton(SearchIndex::class, function ($app) {
            $appId = (string) config('services.algolia.app_id');
            $apiKey = (string) config('services.algolia.api_key');
            if ($appId === '' || $apiKey === '') {
                return new ReadOnlySearchIndex;
            }

            return new AlgoliaSearchIndex(
                $app->make(SearchClient::class),
                (string) config('services.algolia.index_prefix'),
            );
        });

        $this->app->singleton(RssBuilder::class, fn () => new RssBuilder((string) config('externals.rss_host')));
        $this->app->singleton(RssRfcBuilder::class, fn () => new RssRfcBuilder((string) config('externals.rss_host')));
    }

    public function boot(): void
    {
        View::share('version', (string) config('externals.version'));
        View::share('assetsBaseUrl', (string) config('externals.assets_base_url'));
        View::share('algoliaIndex', config('services.algolia.index_prefix').'emails');
        View::share('noIndex', (bool) config('externals.no_index'));
        View::share('debug', (bool) config('app.debug'));
    }
}
