<?php

declare(strict_types=1);

namespace App\Services\Rfc;

/**
 * Status of an RFC, from the section of https://wiki.php.net/rfc that lists it.
 */
enum RfcStatus: string
{
    case Voting = 'voting';
    case Discussion = 'discussion';
    case Draft = 'draft';
    case Accepted = 'accepted';
    case Implemented = 'implemented';
    case Declined = 'declined';
    case Withdrawn = 'withdrawn';
    case Inactive = 'inactive';
    case Obsolete = 'obsolete';

    public static function fromIndexSection(string $section): ?self
    {
        $section = mb_strtolower($section);

        return match (true) {
            str_contains($section, 'voting') => self::Voting,
            str_contains($section, 'discussion') => self::Discussion,
            str_contains($section, 'draft') => self::Draft,
            str_contains($section, 'accepted') => self::Accepted,
            str_contains($section, 'implemented') => self::Implemented,
            str_contains($section, 'declined') => self::Declined,
            str_contains($section, 'withdrawn') => self::Withdrawn,
            str_contains($section, 'inactive') => self::Inactive,
            str_contains($section, 'obsolete') => self::Obsolete,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Voting => 'In voting',
            self::Discussion => 'Under discussion',
            self::Draft => 'Draft',
            self::Accepted => 'Accepted',
            self::Implemented => 'Implemented',
            self::Declined => 'Declined',
            self::Withdrawn => 'Withdrawn',
            self::Inactive => 'Inactive',
            self::Obsolete => 'Obsolete',
        };
    }

    /**
     * Community votes only make sense while the RFC can still change.
     */
    public function acceptsCommunityVotes(): bool
    {
        return match ($this) {
            self::Voting, self::Discussion, self::Draft => true,
            default => false,
        };
    }

    /**
     * Tailwind classes of the status badge.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Voting => 'bg-amber-100 text-amber-800',
            self::Discussion => 'bg-blue-100 text-blue-800',
            self::Draft => 'bg-gray-100 text-gray-600',
            self::Accepted, self::Implemented => 'bg-green-100 text-green-800',
            self::Declined => 'bg-red-100 text-red-800',
            self::Withdrawn, self::Inactive, self::Obsolete => 'bg-gray-100 text-gray-500',
        };
    }
}
