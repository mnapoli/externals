<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $userId
 * @property int $emailNumber
 * @property int $value
 * @property \DateTimeImmutable $updatedAt
 */
class Vote extends Model
{
    /** @use HasFactory<VoteFactory> */
    use HasFactory;

    protected $table = 'votes';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'updatedAt' => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'emailNumber', 'number');
    }

    protected function setKeysForSaveQuery($query): Builder
    {
        return $query
            ->where('userId', $this->getAttribute('userId'))
            ->where('emailNumber', $this->getAttribute('emailNumber'));
    }
}
