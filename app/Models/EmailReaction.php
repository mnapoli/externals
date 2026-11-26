<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Reaction;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * An emoji reaction of a user on an email.
 *
 * @property int $userId
 * @property int $emailNumber
 * @property Reaction $reaction
 * @property DateTimeImmutable $createdAt
 */
class EmailReaction extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'email_reactions';
    protected $guarded = [];
    protected $casts = [
        'reaction' => Reaction::class,
        'createdAt' => 'immutable_datetime',
    ];

    protected function setKeysForSaveQuery($query): Builder
    {
        return $query
            ->where('userId', $this->getAttribute('userId'))
            ->where('emailNumber', $this->getAttribute('emailNumber'))
            ->where('reaction', $this->getRawOriginal('reaction'));
    }
}
