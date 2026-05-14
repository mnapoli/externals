<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Email\EmailAddress;
use Database\Factories\EmailFactory;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property int $number
 * @property string $subject
 * @property string $content
 * @property string $source
 * @property ?string $threadId
 * @property bool $isThreadRoot
 * @property DateTimeImmutable $date
 * @property DateTimeImmutable $fetchDate
 * @property ?string $fromEmail
 * @property ?string $fromName
 * @property ?string $inReplyTo
 * @property-read EmailAddress $from
 * @property-read bool $isRead
 */
class Email extends Model
{
    /** @use HasFactory<EmailFactory> */
    use HasFactory;

    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'emails';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = [
        'date' => 'immutable_datetime',
        'fetchDate' => 'immutable_datetime',
        'isThreadRoot' => 'boolean',
    ];

    /**
     * @return BelongsTo<Thread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class, 'threadId', 'emailId');
    }

    /**
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'emailNumber', 'number');
    }

    /**
     * @return Attribute<EmailAddress, never>
     */
    public function from(): Attribute
    {
        return Attribute::get(
            fn() => new EmailAddress($this->fromEmail, $this->fromName),
        );
    }

    /**
     * @return Attribute<bool, never>
     */
    public function isRead(): Attribute
    {
        return Attribute::get(
            fn() => (bool) ($this->attributes['wasRead'] ?? false),
        );
    }

    public function isThreadRoot(): bool
    {
        return $this->threadId === $this->id;
    }

    public function getUrl(): string
    {
        return '/' . $this->id;
    }
}
