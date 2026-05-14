<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ThreadFactory;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $emailId
 * @property int $emailNumber
 * @property DateTimeImmutable $lastUpdate
 * @property int $emailCount
 * @property int $votes
 */
class Thread extends Model
{
    /** @use HasFactory<ThreadFactory> */
    use HasFactory;

    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'threads';
    protected $primaryKey = 'emailId';
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = [
        'lastUpdate' => 'immutable_datetime',
    ];

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'emailId', 'id');
    }
}
