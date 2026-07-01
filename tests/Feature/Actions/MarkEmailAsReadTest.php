<?php

declare(strict_types=1);

use App\Actions\MarkEmailAsRead;
use App\Models\Email;
use App\Models\User;
use App\Models\UserEmailRead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('creates read record', function (): void {
    Carbon::setTestNow('2026-05-14 10:00:00');
    $email = Email::factory()->create();
    $user = User::factory()->create();

    app(MarkEmailAsRead::class)->handle($email, $user);

    $row = UserEmailRead::where('userId', $user->id)->where('emailId', $email->id)->first();
    $this->assertNotNull($row);
    $this->assertSame('2026-05-14 10:00:00', $row->lastReadDate->format('Y-m-d H:i:s'));
});

test('updates existing read record', function (): void {
    $email = Email::factory()->create();
    $user = User::factory()->create();

    Carbon::setTestNow('2026-01-01 00:00:00');
    app(MarkEmailAsRead::class)->handle($email, $user);

    Carbon::setTestNow('2026-05-14 10:00:00');
    app(MarkEmailAsRead::class)->handle($email, $user);

    $rows = UserEmailRead::where('userId', $user->id)->where('emailId', $email->id)->get();
    $this->assertCount(1, $rows);
    $this->assertSame('2026-05-14 10:00:00', $rows->first()->lastReadDate->format('Y-m-d H:i:s'));
});
