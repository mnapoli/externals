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

    /** The header of most RFCs reads "Name, email" */
    private const string EMAIL_PATTERN = '[\w.+-]+@[\w-]+(?:\.[\w-]+)+';

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
     * Address of the first author, when the wiki gives one.
     */
    public function authorEmail(): ?string
    {
        if (! preg_match('/' . self::EMAIL_PATTERN . '/u', (string) $this->authors, $matches)) {
            return null;
        }

        return $matches[0];
    }

    /**
     * All the authors, without their email addresses, e.g. "Larry Garfield, Ilija Tovilo"
     */
    public function authorNames(): string
    {
        $names = preg_replace(['/[(<]?\s*' . self::EMAIL_PATTERN . '\s*[)>]?/u', '/\(\s*\)/u', '/\s+([,)])/u', '/\s{2,}/u'], ['', '', '$1', ' '], (string) $this->authors);

        return mb_trim((string) $names, " ,\t\n");
    }

    /**
     * The name of the first author, e.g. "Larry Garfield" for "Larry Garfield (larry@garfieldtech.com), Ilija Tovilo"
     */
    public function authorName(): string
    {
        // Before its email or the next author
        return mb_trim(preg_split('/[,(<&\/]|\band\b|' . self::EMAIL_PATTERN . '/u', (string) $this->authors)[0] ?? '');
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
