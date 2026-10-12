<?php

declare(strict_types=1);

use Algolia\AlgoliaSearch\Api\SearchClient;
use App\Models\Email;
use App\Services\Search\AlgoliaEmailSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('search hits carry the number of their thread', function (): void {
    $root = Email::factory()->create(['id' => 'root@example.com', 'number' => 100]);
    Email::factory()->replyTo($root)->create(['id' => 'reply@example.com', 'number' => 101]);

    $client = Mockery::mock(SearchClient::class);
    $client->shouldReceive('searchSingleIndex')->once()->andReturn([
        'hits' => [
            ['objectID' => '101', 'threadId' => 'root@example.com', 'subject' => 'Re: Hello', 'fromName' => 'Bob', 'date' => '2026-01-02T10:00:00+00:00'],
            // Thread root not synchronized: the email is its own thread
            ['objectID' => '200', 'threadId' => 'missing@example.com', 'subject' => 'Orphan', 'fromName' => 'Carol', 'date' => '2026-01-03T10:00:00+00:00'],
        ],
        'nbHits' => 2,
        'nbPages' => 1,
    ]);

    $results = (new AlgoliaEmailSearch($client, 'test_'))->search('hello');

    expect($results->hits[0]->number)->toBe(101)
        ->and($results->hits[0]->threadNumber)->toBe(100)
        ->and($results->hits[1]->threadNumber)->toBe(200);
});
