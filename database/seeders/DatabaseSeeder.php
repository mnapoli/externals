<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\RefreshAllThreads;
use App\Models\Email;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    private static function htmlBody(): string
    {
        return collect(fake()->paragraphs(fake()->numberBetween(1, 4)))
            ->map(fn(string $paragraph): string => "<p>{$paragraph}</p>")
            ->implode('');
    }

    public function run(RefreshAllThreads $refreshAllThreads): void
    {
        $users = User::factory()->count(5)->create();

        // Single-email threads spread over the past year
        Email::factory()
            ->count(15)
            ->state(fn(): array => ['content' => self::htmlBody()])
            ->create();

        // Threads with ongoing discussion
        Email::factory()
            ->count(8)
            ->state(fn(): array => ['content' => self::htmlBody()])
            ->create()
            ->each(fn(Email $root) => $this->addReplies($root, fake()->numberBetween(2, 8)));

        // RFC threads (picked up by the RFC feed, which matches on subject)
        Email::factory()
            ->count(3)
            ->state(fn(): array => [
                'subject' => '[RFC] ' . fake()->sentence(4),
                'content' => self::htmlBody(),
            ])
            ->create()
            ->each(fn(Email $root) => $this->addReplies($root, fake()->numberBetween(2, 6)));

        // Recent upvoted threads, so the "Top" page has content
        // (it only lists threads with votes > 0 updated within the last month)
        Email::factory()
            ->count(5)
            ->state(function (): array {
                $date = fake()->dateTimeBetween('-3 weeks', 'now')->format('Y-m-d H:i:s');

                return ['content' => self::htmlBody(), 'date' => $date, 'fetchDate' => $date];
            })
            ->create()
            ->each(function (Email $root) use ($users): void {
                $this->addReplies($root, fake()->numberBetween(1, 5));

                $users->random(fake()->numberBetween(1, $users->count()))
                    ->each(fn(User $user) => Vote::factory()->create([
                        'userId' => $user->id,
                        'emailNumber' => $root->number,
                        'value' => 1,
                    ]));
            });

        $refreshAllThreads->handle();
    }

    private function addReplies(Email $root, int $count): void
    {
        Email::factory()
            ->count($count)
            ->replyTo($root)
            ->state(function () use ($root): array {
                $date = fake()
                    ->dateTimeBetween($root->date->format('Y-m-d H:i:s'), 'now')
                    ->format('Y-m-d H:i:s');

                return ['content' => self::htmlBody(), 'date' => $date, 'fetchDate' => $date];
            })
            ->create();
    }
}
