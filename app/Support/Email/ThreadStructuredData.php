<?php

declare(strict_types=1);

namespace App\Support\Email;

use App\Models\Email;
use DateTimeInterface;

/**
 * Builds the schema.org DiscussionForumPosting (JSON-LD) of a thread page.
 *
 * Only author names are exposed: email addresses must never end up in the markup.
 */
class ThreadStructuredData
{
    /**
     * @param  ThreadItem[]  $thread  Threaded view, as returned by ThreadQuery::getThreadView().
     * @return array<string, mixed>
     */
    public static function build(array $thread, int $rootNumber, string $subject): array
    {
        $emails = self::flatten($thread);
        usort($emails, fn(Email $a, Email $b) => $a->date <=> $b->date);

        $root = null;
        foreach ($emails as $email) {
            if ($email->number === $rootNumber) {
                $root = $email;
            }
        }
        $root ??= $emails[0] ?? null;

        $url = mb_rtrim((string) config('app.url'), '/') . '/message/' . $rootNumber;

        $comments = [];
        foreach ($emails as $email) {
            if ($email === $root) {
                continue;
            }
            $comments[] = array_filter([
                '@type' => 'Comment',
                'url' => $url . '#' . $email->number,
                'datePublished' => $email->date->format(DateTimeInterface::ATOM),
                'author' => self::author($email),
            ]);
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'DiscussionForumPosting',
            'headline' => $subject,
            'url' => $url,
            'datePublished' => $root?->date->format(DateTimeInterface::ATOM),
            'author' => $root ? self::author($root) : null,
            'interactionStatistic' => [
                '@type' => 'InteractionCounter',
                'interactionType' => 'https://schema.org/CommentAction',
                'userInteractionCount' => count($comments),
            ],
            'comment' => $comments,
        ], fn($value) => $value !== null && $value !== []);
    }

    /**
     * @return array{'@type': string, name: string}|null
     */
    private static function author(Email $email): ?array
    {
        if (! $email->fromName) {
            return null;
        }

        return ['@type' => 'Person', 'name' => $email->fromName];
    }

    /**
     * @param  ThreadItem[]  $items
     * @return Email[]
     */
    private static function flatten(array $items): array
    {
        $emails = [];
        foreach ($items as $item) {
            $emails[] = $item->email;
            array_push($emails, ...self::flatten($item->replies));
        }

        return $emails;
    }
}
