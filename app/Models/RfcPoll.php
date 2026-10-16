<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A vote of an RFC.
 *
 * @property int $id
 * @property int $rfcId
 * @property int $position
 * @property string $question
 * @property bool $isClosed
 * @property ?string $closesAt
 * @property list<array{label: string, count: int}> $choices
 * @property list<array{name: string, choices: list<string>, votedAt: ?string}> $voters
 */
class RfcPoll extends Model
{
    public $timestamps = false;
    protected $table = 'rfc_polls';
    protected $guarded = [];
    protected $casts = [
        'isClosed' => 'boolean',
        'choices' => 'array',
        'voters' => 'array',
    ];

    /**
     * @return BelongsTo<Rfc, $this>
     */
    public function rfc(): BelongsTo
    {
        return $this->belongsTo(Rfc::class, 'rfcId', 'id');
    }

    /**
     * One line summary, e.g. "Yes: 33 · No: 7".
     */
    public function summary(): string
    {
        return implode(' · ', array_map(fn(array $choice) => "{$choice['label']}: {$choice['count']}", $this->choices));
    }

    /**
     * Share of yes needed: 2/3 for the main vote (the first one), a simple majority for the secondary votes.
     */
    public function majority(): float
    {
        return $this->position === 0 ? 2 / 3 : 1 / 2;
    }

    public function hasVotes(): bool
    {
        return array_sum(array_column($this->choices, 'count')) > 0;
    }

    public function stateLabel(): string
    {
        if ($this->isClosed) {
            return 'closed';
        }

        return $this->closesAt !== null ? "open, closes {$this->closesAt}" : 'open';
    }
}
