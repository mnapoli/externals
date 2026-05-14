<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $emailId
 * @property int $emailNumber
 * @property \DateTimeImmutable $lastUpdate
 * @property int $emailCount
 * @property int $votes
 */
class Thread extends Model
{
    protected $table = 'threads';

    protected $primaryKey = 'emailId';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'lastUpdate' => 'immutable_datetime',
    ];

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'emailId', 'id');
    }
}
