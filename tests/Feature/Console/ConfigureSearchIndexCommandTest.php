<?php

declare(strict_types=1);

use Algolia\AlgoliaSearch\Api\SearchClient;
use App\Console\Commands\ConfigureSearchIndexCommand;

function fakeSearchClient(SearchClient $client): void
{
    app()->instance(ConfigureSearchIndexCommand::class, new class($client) extends ConfigureSearchIndexCommand
    {
        public function __construct(
            private readonly SearchClient $client,
        ) {
            parent::__construct();
        }

        protected function searchClient(string $apiKey): SearchClient
        {
            return $this->client;
        }
    });
}

beforeEach(function (): void {
    config(['services.algolia.admin_api_key' => 'admin-key']);
});

test('applies the ranking settings', function (): void {
    $client = Mockery::mock(SearchClient::class);
    $client->shouldReceive('getSettings')->with('v2_emails')->andReturn(['customRanking' => null]);
    $client->shouldReceive('setSettings')->once()->with('v2_emails', [
        'searchableAttributes' => ['subject', 'fromName', 'unordered(extract)'],
        'customRanking' => ['desc(timestamp)'],
    ])->andReturn(['taskID' => 42]);
    $client->shouldReceive('waitForTask')->once()->with('v2_emails', 42);
    fakeSearchClient($client);

    $this->artisan('externals:configure-search-index --prefix=v2_')
        ->expectsConfirmation('Apply the new settings?', 'yes')
        ->assertSuccessful();
});

test('changes nothing in dry run', function (): void {
    $client = Mockery::mock(SearchClient::class);
    $client->shouldReceive('getSettings')->andReturn([]);
    $client->shouldNotReceive('setSettings');
    fakeSearchClient($client);

    $this->artisan('externals:configure-search-index --dry-run')->assertSuccessful();
});
