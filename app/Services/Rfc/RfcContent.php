<?php

declare(strict_types=1);

namespace App\Services\Rfc;

use App\Models\Rfc;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * The text of an RFC, as Markdown (for agents) or HTML (for humans).
 */
class RfcContent
{
    /** @var ?array<string, true> */
    private ?array $knownSlugs = null;

    /**
     * Links to RFCs that are imported point to externals.io, where the
     * discussions are, instead of the wiki.
     */
    public function markdown(Rfc $rfc): string
    {
        return (string) preg_replace_callback(
            '#https?://wiki\.php\.net/rfc[/:]([a-z0-9_.:/%-]+)#i',
            function (array $matches): string {
                $slug = RfcLinkExtractor::normalizeSlug($matches[1]);

                return isset($this->knownSlugs()[$slug]) ? url('/rfc/' . $slug) : $matches[0];
            },
            (string) $rfc->content,
        );
    }

    public function html(Rfc $rfc): string
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($this->markdown($rfc))->getContent();
    }

    /**
     * @return array<string, true>
     */
    private function knownSlugs(): array
    {
        return $this->knownSlugs ??= array_fill_keys(Rfc::pluck('slug')->all(), true);
    }
}
