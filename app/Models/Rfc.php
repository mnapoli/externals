<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Rfc\RfcPageParser;
use App\Services\Rfc\RfcStatus;
use Database\Factories\RfcFactory;
use DateTimeImmutable;
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

    public function url(): string
    {
        return url('/rfc/' . $this->slug);
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
}
