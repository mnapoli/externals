<?php

use App\Exceptions\NotFoundException;
use App\Models\Rfc;
use App\Models\RfcVote;
use App\Services\Email\AvatarUrls;
use App\Services\Email\ThreadQuery;
use App\Services\Rfc\RfcContent;
use App\Services\Rfc\RfcLinkExtractor;
use App\Services\Rfc\RfcThreads;
use App\Support\CodeHighlighter;
use App\Support\HtmlHeadings;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $slug = '';

    public function mount(string $slug): mixed
    {
        $normalized = RfcLinkExtractor::normalizeSlug($slug);
        if (! Rfc::where('slug', $normalized)->exists()) {
            throw new NotFoundException("RFC $slug was not found");
        }
        if ($normalized !== $slug) {
            return $this->redirect("/rfc/$normalized");
        }
        $this->slug = $slug;

        return null;
    }

    public function rendering(View $view): void
    {
        $rfc = $this->rfc();
        $view->title('RFC: ' . $rfc->title . ' - Externals');
        // The original is on the wiki: don't compete with it in search engines
        $view->layoutData(['canonical' => $rfc->wikiUrl(), 'ogUrl' => $rfc->url()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $rfc = $this->rfc();
        ['discussions' => $threads, 'mentions' => $mentions] = app(RfcThreads::class)->discussionsOf($rfc);

        $content = $rfc->content !== null
            ? HtmlHeadings::anchor(app(CodeHighlighter::class)->highlight(app(RfcContent::class)->html($rfc)), 'rfc-')
            : null;

        return [
            'rfc' => $rfc,
            'threads' => $threads,
            'mentions' => $mentions,
            'participants' => app(ThreadQuery::class)->participantsOf($threads->pluck('number')->all()),
            'authorAvatar' => app(AvatarUrls::class)->of([$rfc->authorEmail()], 64)[$rfc->authorEmail() ?? ''] ?? null,
            'contentHtml' => $content['html'] ?? null,
            'headings' => $content['headings'] ?? [],
            'userVote' => auth()->check() ? (int) RfcVote::where('userId', auth()->id())->where('rfcId', $rfc->id)->value('value') : 0,
        ];
    }

    private function rfc(): Rfc
    {
        return Rfc::with('polls')->withVoteCounts()->where('slug', $this->slug)->firstOrFail();
    }
}; ?>

@push('head')
    <link rel="alternate" type="text/markdown" href="/rfc/{{ $rfc->slug }}.md" title="RFC as Markdown">
@endpush

@php($statusLabel = $rfc->status->badgeLabel() . ($rfc->phpVersion !== null ? " · PHP {$rfc->phpVersion}" : ''))
{{-- Links in the metadata of the wiki, with short labels: "php/php-src#17118" rather than the whole URL --}}
@php($linkify = fn(?string $value) => preg_replace_callback('#https?://[^\s<>"]+[^\s<>".,;:)]#', function (array $matches): string {
    $label = preg_match('#^https?://github\.com/([^/]+/[^/]+)/(?:pull|issues)/(\d+)#', $matches[0], $github) ? "{$github[1]}#{$github[2]}"
        : (preg_match('#^https?://github\.com/([^/]+/[^/]+)/commit/([0-9a-f]{7})#', $matches[0], $github) ? "{$github[1]}@{$github[2]}"
        : preg_replace('#^https?://(www\.)?#', '', $matches[0]));

    return '<a class="text-accent-strong underline decoration-accent-line underline-offset-2 hover:decoration-accent-strong" href="' . $matches[0] . '">' . $label . '</a>';
}, e((string) $value)))

<div class="mx-auto max-w-[66rem] lg:grid lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-x-12 lg:items-start">
    <header class="mb-6 lg:mb-8 lg:col-start-1">
        <a href="/rfc" class="text-sm text-muted hover:text-ink">← All RFCs</a>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="label px-1.5 py-0.5 rounded {{ $rfc->status->badgeClasses() }}">{{ $statusLabel }}</span>
            @if(! $rfc->isListed)
                <span class="text-xs text-muted">no longer listed on the wiki index</span>
            @endif
        </div>
        <h1 class="mt-2 text-2xl sm:text-[2rem] leading-tight font-bold tracking-tight [overflow-wrap:anywhere]">{{ $rfc->title }}</h1>
        <div class="mt-3 flex items-center gap-2.5 text-sm text-ink-soft">
            @if($rfc->authorName() !== '')
                <span class="relative w-7 h-7 shrink-0 rounded-full bg-[#e4e4ea] text-[10px] font-semibold text-ink-soft flex items-center justify-center select-none">
                    <span aria-hidden="true">{{ $rfc->authorInitials() }}</span>
                    @if($authorAvatar)
                        <img src="{{ $authorAvatar }}" alt="" class="absolute inset-0 w-full h-full rounded-full object-cover">
                    @endif
                </span>
                <span class="min-w-0">by <span class="text-ink font-medium">{{ $rfc->authorNames() }}</span></span>
            @endif
        </div>
        <p class="mt-2 font-mono text-xs text-muted">
            @if($rfc->version)v{{ $rfc->version }} · @endif
            @if($rfc->date)created {{ $rfc->date }} · @endif
            <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="{{ $rfc->wikiUrl() }}">wiki.php.net ↗</a>
        </p>
    </header>

    <aside class="space-y-4 mb-8 lg:mb-0 lg:col-start-2 lg:row-start-1 lg:row-span-2 lg:self-stretch">
        @if($rfc->polls->isNotEmpty())
            <section class="bg-surface border border-line rounded-lg overflow-hidden" aria-labelledby="official-vote">
                <div class="flex items-baseline justify-between gap-2 px-4 py-2.5 border-b border-line">
                    <h2 id="official-vote" class="label text-ink-soft">Official {{ Str::plural('vote', $rfc->polls->count()) }}</h2>
                    <span class="text-xs text-muted">by php-src voters</span>
                </div>
                @foreach($rfc->polls as $poll)
                    @php($total = array_sum(array_column($poll->choices, 'count')))
                    @php($closesAt = \App\Services\Rfc\RfcHighlights::closesAt($poll))
                    @php($yesShare = \App\Services\Rfc\RfcHighlights::yesShare($poll))
                    <div class="px-4 py-3 border-b border-line last:border-b-0">
                        <p class="text-sm font-medium text-ink">{{ $poll->question }}</p>
                        <p class="mt-0.5 font-mono text-xs text-muted">
                            @if($poll->isClosed)
                                closed
                            @elseif($closesAt)
                                <span title="{{ $poll->closesAt }}">{{ $closesAt->isFuture() ? 'closes in ' . $closesAt->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) : 'closed ' . $closesAt->diffForHumans() }}</span>
                            @else
                                open
                            @endif
                            @if($yesShare !== null)
                                · <span class="{{ $yesShare >= 2 / 3 ? 'text-pass' : 'text-fail' }}">{{ round($yesShare * 100) }}% yes{{ $poll->isClosed ? '' : ($yesShare >= 2 / 3 ? ', passing' : ', failing') }}</span>
                            @endif
                        </p>
                        @if($total > 0)
                            <div class="mt-2.5 space-y-1.5">
                                @foreach($poll->choices as $choice)
                                    @php($isYes = mb_strtolower($choice['label']) === 'yes')
                                    <div class="grid grid-cols-[minmax(0,6rem)_minmax(0,1fr)_2rem] items-center gap-2 text-xs">
                                        <span class="truncate text-ink-soft" title="{{ $choice['label'] }}">{{ $choice['label'] }}</span>
                                        <span class="relative h-1.5 rounded-full bg-subtle">
                                            <span class="absolute inset-y-0 left-0 rounded-full {{ $isYes ? 'bg-pass' : ($loop->first ? 'bg-accent' : 'bg-faint') }}" style="width: {{ round($choice['count'] / $total * 100, 1) }}%"></span>
                                        </span>
                                        <span class="font-mono text-right text-ink">{{ $choice['count'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-1 text-xs text-muted">No votes yet · {{ implode(' · ', array_column($poll->choices, 'label')) }}</p>
                        @endif
                        @if($poll->voters)
                            <details class="mt-2.5 text-xs">
                                <summary class="cursor-pointer font-mono text-muted hover:text-ink">{{ count($poll->voters) }} {{ Str::plural('voter', count($poll->voters)) }}</summary>
                                <ul class="mt-2 max-h-72 overflow-y-auto space-y-1">
                                    @foreach($poll->voters as $voter)
                                        <li class="flex items-baseline gap-2">
                                            <a class="text-ink hover:text-accent truncate" href="https://people.php.net/{{ $voter['name'] }}">{{ $voter['name'] }}</a>
                                            <span class="ml-auto shrink-0 text-ink-soft">{{ implode(', ', $voter['choices']) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        @if($rfc->status->acceptsCommunityVotes() || $rfc->upvotes || $rfc->downvotes)
            <section class="bg-surface border border-line rounded-lg px-4 py-3" aria-labelledby="community-opinion">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="community-opinion" class="label text-ink-soft">Community opinion</h2>
                    <livewire:rfc-vote :rfc-id="$rfc->id" :can-vote="$rfc->status->acceptsCommunityVotes()"
                                       :upvotes="(int) $rfc->upvotes" :downvotes="(int) $rfc->downvotes"
                                       :user-vote="$userVote" />
                </div>
                <p class="mt-1 text-xs text-muted">On externals.io, not the official vote.</p>
            </section>
        @endif

        <section class="bg-surface border border-line rounded-lg overflow-hidden" aria-labelledby="discussions">
            <div class="flex items-baseline justify-between gap-2 px-4 py-2.5 border-b border-line">
                <h2 id="discussions" class="label text-ink-soft">Discussions</h2>
                @if($threads->isNotEmpty())<span class="font-mono text-xs text-muted">{{ $threads->count() }}</span>@endif
            </div>
            @forelse($threads as $thread)
                <div wire:key="thread-{{ $thread->number }}" class="px-4 py-3 border-b border-line last:border-b-0">
                    <a class="block text-sm font-medium text-ink hover:text-accent [overflow-wrap:anywhere]" href="/message/{{ $thread->number }}">{{ $thread->subject }}</a>
                    <div class="mt-1.5 flex items-center gap-2 min-w-0">
                        @if($participants[$thread->number] ?? null)
                            <x-participants :participants="$participants[$thread->number]" :max="6" :max-mobile="6" size="w-[22px] h-[22px] text-[9px]" />
                        @endif
                        <span class="font-mono text-xs text-muted truncate">
                            {{ $thread->emailCount }} {{ Str::plural('message', $thread->emailCount) }} · <time datetime="{{ $thread->date->toAtomString() }}" title="{{ $thread->date->format('D, M j, Y') }}">{{ $thread->date->format('M Y') }}</time>
                        </span>
                    </div>
                </div>
            @empty
                <p class="px-4 py-3 text-sm text-muted">
                    No discussion found yet.
                    <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="/search?q={{ urlencode($rfc->title) }}">Search the mailing list</a>
                </p>
            @endforelse
            @if($mentions->isNotEmpty())
                <details class="group/mentions border-t border-line">
                    <summary class="list-none [&::-webkit-details-marker]:hidden flex items-center gap-2 px-4 py-2.5 cursor-pointer font-mono text-xs text-muted hover:text-ink hover:bg-subtle">
                        <svg class="w-3 h-3 -rotate-90 group-open/mentions:rotate-0 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                        Mentioned in {{ $mentions->count() }} other {{ Str::plural('thread', $mentions->count()) }}
                    </summary>
                    @foreach($mentions as $thread)
                        <div wire:key="mention-{{ $thread->number }}" class="px-4 py-2 border-t border-line">
                            <a class="block text-[13px] text-ink-soft hover:text-accent [overflow-wrap:anywhere]" href="/message/{{ $thread->number }}">{{ $thread->subject }}</a>
                            <span class="font-mono text-xs text-muted">{{ $thread->emailCount }} {{ Str::plural('message', $thread->emailCount) }} · {{ $thread->date->format('M Y') }}</span>
                        </div>
                    @endforeach
                </details>
            @endif
        </section>

        <section class="bg-surface border border-line rounded-lg px-4 py-3" aria-labelledby="details">
            <h2 id="details" class="label text-ink-soft mb-2">Details</h2>
            <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1.5 text-[13px]">
                @foreach(['Authors' => $rfc->authors, 'Created' => $rfc->date, 'Version' => $rfc->version, 'Target' => $rfc->targetVersion, 'Implementation' => $rfc->implementation] as $label => $value)
                    @if($value)
                        <dt class="text-muted">{{ $label }}</dt>
                        <dd class="text-ink [overflow-wrap:anywhere]">{!! $linkify($value) !!}</dd>
                    @endif
                @endforeach
            </dl>
        </section>

        @if(count($headings) > 2)
            {{-- Sticks once the cards above are scrolled past --}}
            <nav aria-label="Contents" class="hidden lg:block sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto bg-surface border border-line rounded-lg px-2 py-3"
                 x-data="{ current: null }"
                 x-init="const headings = [...document.querySelectorAll('.rfc-content h2[id], .rfc-content h3[id]')];
                    const update = () => { let current = null; for (const h of headings) { if (h.getBoundingClientRect().top < 120) current = h.id } $data.current = current };
                    window.addEventListener('scroll', () => requestAnimationFrame(update), { passive: true }); update()">
                <h2 class="label text-ink-soft px-2 mb-1.5">Contents</h2>
                <ol>
                    @foreach($headings as $heading)
                        <li>
                            <a href="#{{ $heading['id'] }}" :class="current === @js($heading['id']) ? 'bg-accent-soft text-ink' : ''"
                               class="block px-2 py-1 rounded text-[13px] leading-snug hover:bg-subtle {{ $heading['level'] === 3 ? 'pl-5 text-muted' : 'text-ink-soft' }}">{{ $heading['text'] }}</a>
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
    </aside>

    <article class="lg:col-start-1 min-w-0 max-w-[72ch] text-[15px] sm:text-base" aria-label="Text of the RFC">
        @if(count($headings) > 2)
            <details class="lg:hidden mb-6 bg-surface border border-line rounded-lg group/contents">
                <summary class="list-none [&::-webkit-details-marker]:hidden flex items-center justify-between px-4 py-3 cursor-pointer">
                    <span class="label text-ink-soft">Contents</span>
                    <svg class="w-3.5 h-3.5 text-faint -rotate-90 group-open/contents:rotate-0 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                </summary>
                <ol class="px-2 pb-3">
                    @foreach($headings as $heading)
                        <li><a href="#{{ $heading['id'] }}" class="block px-2 py-2 rounded text-sm hover:bg-subtle {{ $heading['level'] === 3 ? 'pl-5 text-muted' : 'text-ink-soft' }}">{{ $heading['text'] }}</a></li>
                    @endforeach
                </ol>
            </details>
        @endif
        <p class="mb-5 pb-3 border-b border-line font-mono text-xs text-muted">
            Text from <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="{{ $rfc->wikiUrl() }}">wiki.php.net</a>@if($rfc->fetchedAt) · <span title="{{ $rfc->fetchedAt->format('M j, Y H:i') }} UTC">synced {{ $rfc->fetchedAt->diffForHumans() }}</span>@endif
        </p>
        @if($contentHtml !== null)
            <div class="rfc-content">
                {!! $contentHtml !!}
            </div>
        @else
            <p class="text-sm text-muted">The text of this RFC has not been imported yet: read it on <a class="underline" href="{{ $rfc->wikiUrl() }}">wiki.php.net</a>.</p>
        @endif
    </article>
</div>
