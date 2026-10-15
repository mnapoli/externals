<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Rfc\RfcPageParser;
use App\Services\Rfc\RfcStatus;
use App\Support\Email\EmailAddress;
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
     * Gravatar of the first author, transparent when there is none (the initials show through).
     */
    public function authorAvatarUrl(): ?string
    {
        if (! preg_match('/' . self::EMAIL_PATTERN . '/u', (string) $this->authors, $matches)) {
            return null;
        }

        return EmailAddress::avatarUrlOf($matches[0], 48);
    }

    /**
     * e.g. "LG" for "Larry Garfield (larry@garfieldtech.com), Ilija Tovilo"
     */
    public function authorInitials(): string
    {
        // The name of the first author: before its email or the next author
        $name = preg_split('/[,(<&\/]|\band\b|' . self::EMAIL_PATTERN . '/u', (string) $this->authors)[0] ?? '';
        $words = array_values(array_filter(
            preg_split('/\s+/u', mb_trim($name)) ?: [],
            fn(string $word) => preg_match('/^\p{L}/u', $word) === 1,
        ));
        if ($words === []) {
            return '';
        }
        $initials = mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : '');

        return mb_strtoupper($initials);
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
