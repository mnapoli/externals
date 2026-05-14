<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $githubId
 * @property string $name
 */
class User extends Model
{
    protected $table = 'users';

    public $timestamps = false;

    protected $guarded = [];
}
