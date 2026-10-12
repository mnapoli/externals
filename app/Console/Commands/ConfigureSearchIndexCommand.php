<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Illuminate\Console\Command;

/**
 * Versions the ranking settings of the Algolia index instead of editing them in the dashboard.
 */
class ConfigureSearchIndexCommand extends Command
{
    /**
     * Ordered by priority: a match in the subject ranks above a match in the content.
     * The position of the match in the content doesn't matter.
     */
    private const array SEARCHABLE_ATTRIBUTES = ['subject', 'fromName', 'unordered(extract)'];

    /** Between equally relevant results, the most recent first */
    private const array CUSTOM_RANKING = ['desc(timestamp)'];

    protected $signature = 'externals:configure-search-index
                            {--prefix= : Index prefix (defaults to ALGOLIA_INDEX_PREFIX)}
                            {--dry-run : Only show the current and new settings}';
    protected $description = 'Apply the ranking settings of the Algolia emails index (needs an admin API key)';

    public function handle(): int
    {
        $prefix = (string) ($this->option('prefix') ?? config('services.algolia.index_prefix'));
        $indexName = $prefix . 'emails';
        $apiKey = (string) config('services.algolia.admin_api_key')
            ?: (string) $this->secret('Algolia API key with the "settings" and "editSettings" ACLs');

        $client = $this->searchClient($apiKey);
        /** @var array<string, mixed> $current */
        $current = $client->getSettings($indexName);

        $this->info("Index: $indexName");
        $this->table(['Setting', 'Current', 'New'], [
            ['searchableAttributes', json_encode($current['searchableAttributes'] ?? null), json_encode(self::SEARCHABLE_ATTRIBUTES)],
            ['customRanking', json_encode($current['customRanking'] ?? null), json_encode(self::CUSTOM_RANKING)],
        ]);

        if ($this->option('dry-run') || ! $this->confirm('Apply the new settings?')) {
            return self::SUCCESS;
        }

        // Only the given settings change, the others are kept
        /** @var array{taskID: int} $response */
        $response = $client->setSettings($indexName, [
            'searchableAttributes' => self::SEARCHABLE_ATTRIBUTES,
            'customRanking' => self::CUSTOM_RANKING,
        ]);
        $client->waitForTask($indexName, $response['taskID']);
        $this->info('Settings applied. Algolia reindexes in the background, results may take a few minutes to change.');

        return self::SUCCESS;
    }

    protected function searchClient(string $apiKey): SearchClient
    {
        return SearchClient::create((string) config('services.algolia.app_id'), $apiKey);
    }
}
