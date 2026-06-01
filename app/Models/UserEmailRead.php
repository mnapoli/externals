<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $userId
 * @property string $emailId
 * @property DateTimeImmutable $lastReadDate
 */
class UserEmailRead extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'user_emails_read';
    protected $guarded = [];
    protected $casts = [
        'lastReadDate' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

    /**
     * @return BelongsTo<Email, $this>
     */
    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'emailId', 'id');
    }

    protected function setKeysForSaveQuery($query): Builder
    {
        return $query
            ->where('userId', $this->getAttribute('userId'))
            ->where('emailId', $this->getAttribute('emailId'));
    }
}
