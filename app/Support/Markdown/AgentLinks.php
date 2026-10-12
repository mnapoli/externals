<?php

declare(strict_types=1);

namespace App\Support\Markdown;

/**
 * Points agents reading a Markdown response to the other ways to use the site.
 *
 * Agents usually get Markdown through content negotiation without ever seeing the
 * HTML footer, so Markdown responses must say it themselves. Kept near the top:
 * long threads are often truncated by the agent's fetch tool.
 */
class AgentLinks
{
    public static function markdown(): string
    {
        return sprintf(
            'More for agents: search with %s · JSON API at %s · documentation at %s',
            url('/search.md') . '?q={query}',
            url('/api'),
            url('/llms.txt'),
        );
    }
}
