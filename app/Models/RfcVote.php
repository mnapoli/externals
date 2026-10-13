<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A community vote on an RFC (not the official vote of the wiki).
 *
 * @property int $userId
 * @property int $rfcId
 * @property int $value 1 or -1
 * @property DateTimeImmutable $updatedAt
 */
class RfcVote extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'rfc_votes';
    protected $guarded = [];
    protected $casts = [
        'updatedAt' => 'immutable_datetime',
    ];

    protected function setKeysForSaveQuery($query): Builder
    {
        return $query
            ->where('userId', $this->getAttribute('userId'))
            ->where('rfcId', $this->getAttribute('rfcId'));
    }
}
