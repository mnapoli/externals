<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $githubId
 * @property string $name
 */
class User extends Model implements Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    public $timestamps = false;
    protected $table = 'users';
    protected $guarded = [];

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getAuthPasswordName(): string
    {
        return '';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'userId', 'id');
    }

    /**
     * @return HasMany<UserEmailRead, $this>
     */
    public function reads(): HasMany
    {
        return $this->hasMany(UserEmailRead::class, 'userId', 'id');
    }
}
