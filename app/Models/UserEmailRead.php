<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $userId
 * @property string $emailId
 * @property \DateTimeImmutable $lastReadDate
 */
class UserEmailRead extends Model
{
    protected $table = 'user_emails_read';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'lastReadDate' => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'emailId', 'id');
    }
}
