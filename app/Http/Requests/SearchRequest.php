<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Search\EmailSearch;
use App\Services\Search\SearchResults;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'since' => ['sometimes', 'date_format:Y-m-d'],
            'until' => ['sometimes', 'date_format:Y-m-d'],
        ];
    }

    public function searchQuery(): string
    {
        return mb_trim((string) $this->validated('q'));
    }

    /**
     * Returns null when there is nothing to search.
     */
    public function results(EmailSearch $search): ?SearchResults
    {
        if ($this->searchQuery() === '') {
            return null;
        }

        $since = $this->validated('since');
        $until = $this->validated('until');

        return $search->search(
            $this->searchQuery(),
            (int) $this->validated('page', 1),
            $since ? CarbonImmutable::parse($since)->startOfDay() : null,
            $until ? CarbonImmutable::parse($until)->endOfDay() : null,
        );
    }

    /**
     * URL of another page of the same search.
     */
    public function pageUrl(string $path, int $page): string
    {
        $parameters = array_filter([
            'q' => $this->searchQuery(),
            'since' => $this->validated('since'),
            'until' => $this->validated('until'),
            'page' => $page > 1 ? $page : null,
        ]);

        return url($path . '?' . http_build_query($parameters));
    }
}
