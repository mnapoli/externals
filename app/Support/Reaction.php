<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The emoji reactions on emails: GitHub's set, with GitHub's API names.
 */
enum Reaction: string
{
    case ThumbsUp = '+1';
    case ThumbsDown = '-1';
    case Laugh = 'laugh';
    case Hooray = 'hooray';
    case Confused = 'confused';
    case Heart = 'heart';
    case Rocket = 'rocket';
    case Eyes = 'eyes';

    public function emoji(): string
    {
        return match ($this) {
            self::ThumbsUp => '👍',
            self::ThumbsDown => '👎',
            self::Laugh => '😄',
            self::Hooray => '🎉',
            self::Confused => '😕',
            self::Heart => '❤️',
            self::Rocket => '🚀',
            self::Eyes => '👀',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ThumbsUp => 'Thumbs up',
            self::ThumbsDown => 'Thumbs down',
            self::Laugh => 'Laugh',
            self::Hooray => 'Hooray',
            self::Confused => 'Confused',
            self::Heart => 'Heart',
            self::Rocket => 'Rocket',
            self::Eyes => 'Eyes',
        };
    }
}
