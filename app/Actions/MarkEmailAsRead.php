<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Email;
use App\Models\User;
use App\Models\UserEmailRead;
use Illuminate\Support\Carbon;

class MarkEmailAsRead
{
    public function handle(Email $email, User $user): void
    {
        UserEmailRead::updateOrCreate(
            ['emailId' => $email->id, 'userId' => $user->id],
            ['lastReadDate' => Carbon::now('UTC')],
        );
    }
}
