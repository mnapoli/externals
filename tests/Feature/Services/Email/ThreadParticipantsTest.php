<?php

declare(strict_types=1);

use App\Actions\RefreshAllThreads;
use App\Models\Email;
use App\Services\Email\ThreadQuery;
use App\Support\Email\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the author of the thread comes first, then the most active participants', function (): void {
    $root = Email::factory()->create(['number' => 1, 'fromName' => 'Author', 'fromEmail' => 'author@example.com']);
    Email::factory()->replyTo($root)->create(['fromName' => 'Occasional', 'fromEmail' => 'occasional@example.com', 'date' => '2026-01-01 10:00:00']);
    foreach (range(1, 3) as $i) {
        Email::factory()->replyTo($root)->create(['fromName' => 'Chatty', 'fromEmail' => 'chatty@example.com']);
    }
    Email::factory()->replyTo($root)->create(['fromName' => 'Author', 'fromEmail' => 'author@example.com']);
    Email::factory()->create(['number' => 2, 'fromName' => 'Other thread', 'fromEmail' => 'other@example.com']);

    $participants = app(ThreadQuery::class)->participantsOf([1, 2]);

    expect(array_map(fn(Participant $p) => [$p->name, $p->messageCount], $participants[1]))->toBe([
        ['Author', 2],
        ['Chatty', 3],
        ['Occasional', 1],
    ]);
    expect($participants[2])->toHaveCount(1);
});

test('initials and avatar of a participant', function (): void {
    $participant = new Participant('Rowan Tommins [IMSoP]', 'rowan@example.com', 3);

    expect($participant->initials())->toBe('RT');
    expect($participant->avatarUrl())->toBe('https://www.gravatar.com/avatar/' . hash('sha256', 'rowan@example.com') . '?s=64&d=blank');
    expect((new Participant('anonymous', null, 1))->avatarUrl())->toBeNull();
});

test('the thread list stacks up to 10 faces, then says how many more', function (): void {
    $root = Email::factory()->create(['number' => 1]);
    foreach (range(1, 14) as $i) {
        Email::factory()->replyTo($root)->create(['fromName' => "Person $i", 'fromEmail' => "person$i@example.com"]);
    }
    app(RefreshAllThreads::class)->handle();

    $html = $this->get('/')->getContent();

    // 15 people: 9 faces and "+6" on desktop, 5 faces and "+10" on mobile
    expect($html)->toContain('>+6</span>')->toContain('>+10</span>')->toContain('15 participants');
});
