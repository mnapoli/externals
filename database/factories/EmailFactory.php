<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Email;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Email>
 */
class EmailFactory extends Factory
{
    protected $model = Email::class;

    public function definition(): array
    {
        $id = '<'.fake()->uuid().'@example.com>';
        $date = fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s');

        return [
            'id' => $id,
            'number' => fake()->unique()->numberBetween(1, 999_999_999),
            'subject' => fake()->sentence(),
            'content' => '',
            'source' => '',
            'threadId' => $id,
            'isThreadRoot' => true,
            'date' => $date,
            'fetchDate' => $date,
            'fromEmail' => fake()->safeEmail(),
            'fromName' => fake()->name(),
            'inReplyTo' => null,
        ];
    }

    /**
     * Keep threadId in sync with id for thread roots, even when callers
     * override only the id — the model invariant is "root.threadId === root.id".
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Email $email) {
            if ($email->isThreadRoot && $email->threadId !== $email->id) {
                $email->threadId = $email->id;
            }
        });
    }

    /**
     * Reply within an existing thread.
     */
    public function replyTo(Email $parent): self
    {
        return $this->state([
            'threadId' => $parent->threadId,
            'isThreadRoot' => false,
            'inReplyTo' => $parent->id,
        ]);
    }
}
