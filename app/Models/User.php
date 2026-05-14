<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $githubId
 * @property string $name
 */
class User extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $table = 'users';

    public $timestamps = false;

    protected $guarded = [];

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'userId', 'id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(UserEmailRead::class, 'userId', 'id');
    }
}
