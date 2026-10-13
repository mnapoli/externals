<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Rfc\RfcPageParser;
use App\Services\Rfc\RfcStatus;
use Database\Factories\RfcFactory;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An RFC imported from https://wiki.php.net/rfc.
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property RfcStatus $status
 * @property ?string $indexSubsection
 * @property ?string $phpVersion
 * @property int $indexPosition
 * @property bool $isListed
 * @property ?string $version
 * @property ?string $date
 * @property ?string $authors
 * @property ?string $rawStatus
 * @property ?string $targetVersion
 * @property ?string $implementation
 * @property ?string $content
 * @property ?DateTimeImmutable $fetchedAt
 * @property-read ?int $upvotes Only with the `withVoteCounts()` scope
 * @property-read ?int $downvotes Only with the `withVoteCounts()` scope
 */
class Rfc extends Model
{
    /** @use HasFactory<RfcFactory> */
    use HasFactory;

    public $timestamps = false;
    protected $table = 'rfcs';
    protected $guarded = [];
    protected $casts = [
        'status' => RfcStatus::class,
        'isListed' => 'boolean',
        'fetchedAt' => 'immutable_datetime',
    ];

    /**
     * @return HasMany<RfcPoll, $this>
     */
    public function polls(): HasMany
    {
        return $this->hasMany(RfcPoll::class, 'rfcId', 'id')->orderBy('position');
    }

    /**
     * Community votes (not the official vote of the wiki).
     *
     * @return HasMany<RfcVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(RfcVote::class, 'rfcId', 'id');
    }

    public function url(): string
    {
        return url('/rfc/' . $this->slug);
    }

    /**
     * Gravatar of the first author, transparent when there is none.
     *
     * Gravatar rather than unavatar.io (used for emails): the list of RFCs shows ~200 avatars,
     * which exceeds the rate limit of unavatar.io.
     */
    public function authorAvatarUrl(): ?string
    {
        // The header of most RFCs reads "Name, email"
        if (! preg_match('/[\w.+-]+@[\w-]+(?:\.[\w-]+)+/u', (string) $this->authors, $matches)) {
            return null;
        }

        return 'https://www.gravatar.com/avatar/' . hash('sha256', mb_strtolower($matches[0])) . '?s=48&d=blank';
    }

    public function wikiUrl(): string
    {
        return RfcPageParser::WIKI_URL . '/rfc/' . $this->slug;
    }

    /**
     * e.g. "Implemented (PHP 8.5)"
     */
    public function statusLabel(): string
    {
        return $this->status->label() . ($this->phpVersion !== null ? " (PHP {$this->phpVersion})" : '');
    }

    /**
     * Counts of community votes, as `upvotes` and `downvotes` attributes.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withVoteCounts(Builder $query): void
    {
        $query->withCount([
            'votes as upvotes' => fn(Builder $votes) => $votes->where('value', 1),
            'votes as downvotes' => fn(Builder $votes) => $votes->where('value', -1),
        ]);
    }
}
