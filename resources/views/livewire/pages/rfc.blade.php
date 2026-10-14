<?php

use App\Exceptions\NotFoundException;
use App\Models\Rfc;
use App\Models\RfcVote;
use App\Services\Rfc\RfcContent;
use App\Services\Rfc\RfcLinkExtractor;
use App\Services\Rfc\RfcThreads;
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

        return [
            'rfc' => $rfc,
            'threads' => app(RfcThreads::class)->threadsOf($rfc->slug),
            'contentHtml' => $rfc->content !== null ? app(RfcContent::class)->html($rfc) : null,
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

<div class="mx-auto max-w-4xl">
    <a href="/rfc" class="text-sm text-muted hover:text-ink">← All RFCs</a>
    <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight mb-3 break-words">{{ $rfc->title }}</h1>

    <div class="mb-6 text-sm flex flex-wrap items-baseline gap-x-4 gap-y-2">
        <div>
            <span class="label inline-block rounded px-1.5 py-0.5 {{ $rfc->status->badgeClasses() }}">{{ $rfc->statusLabel() }}</span>
            @if(! $rfc->isListed)
                <span class="ml-1 text-xs text-muted">no longer listed on the wiki index</span>
            @endif
        </div>
        @if($rfc->status->acceptsCommunityVotes() || $rfc->upvotes || $rfc->downvotes)
            <div class="flex items-baseline gap-2">
                <livewire:rfc-vote :rfc-id="$rfc->id" :can-vote="$rfc->status->acceptsCommunityVotes()"
                                   :upvotes="(int) $rfc->upvotes" :downvotes="(int) $rfc->downvotes"
                                   :user-vote="$userVote" />
                <span class="text-xs text-muted">community opinion on externals.io, not the official vote</span>
            </div>
        @endif
    </div>

    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm mb-10">
        @foreach(['Authors' => $rfc->authors, 'Date' => $rfc->date, 'Version' => $rfc->version, 'Target' => $rfc->targetVersion, 'Implementation' => $rfc->implementation] as $label => $value)
            @if($value)
                <dt class="text-muted">{{ $label }}</dt>
                <dd class="text-ink break-words min-w-0">{!! preg_replace('#https?://[^\s<>"]+[^\s<>".,;:)]#', '<a class="text-accent-strong underline underline-offset-2" href="$0">$0</a>', e($value)) !!}</dd>
            @endif
        @endforeach
        <dt class="text-muted">Source</dt>
        <dd class="min-w-0 break-words"><a class="text-accent-strong underline underline-offset-2" href="{{ $rfc->wikiUrl() }}">{{ Str::after($rfc->wikiUrl(), 'https://') }}</a></dd>
    </dl>

    @if($rfc->polls->isNotEmpty())
        <section class="mb-10">
            <h2 class="text-lg font-semibold mb-4">Official {{ Str::plural('vote', $rfc->polls->count()) }} <span class="text-sm font-normal text-muted">by php-src voters</span></h2>
            @foreach($rfc->polls as $poll)
                <div class="mb-6">
                    <div class="mb-2">
                        <span class="text-ink">{{ $poll->question }}</span>
                        <span class="text-xs text-muted ml-1">{{ $poll->hasVotes() ? $poll->stateLabel() : 'no votes' }}</span>
                    </div>
                    @if(! $poll->hasVotes())
                        <div class="text-sm text-muted">{{ implode(' · ', array_column($poll->choices, 'label')) }}</div>
                    @else
                        @php($total = array_sum(array_column($poll->choices, 'count')))
                        <div class="space-y-1 text-sm">
                            @foreach($poll->choices as $choice)
                                <div class="flex items-center gap-3">
                                    <div class="w-28 shrink-0 truncate text-ink-soft" title="{{ $choice['label'] }}">{{ $choice['label'] }}</div>
                                    <div class="flex-grow bg-subtle rounded-full h-2 overflow-hidden">
                                        <div class="h-2 {{ $loop->first ? 'bg-pass' : 'bg-faint' }}" style="width: {{ round($choice['count'] / $total * 100, 1) }}%"></div>
                                    </div>
                                    <div class="w-10 shrink-0 text-right text-ink">{{ $choice['count'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if($poll->voters)
                        <details class="mt-2 text-sm">
                            <summary class="cursor-pointer text-muted hover:text-ink-soft">{{ count($poll->voters) }} {{ Str::plural('voter', count($poll->voters)) }}</summary>
                            <table class="mt-2 text-xs">
                                @foreach($poll->voters as $voter)
                                    <tr>
                                        <td class="pr-4 py-0.5"><a class="text-ink hover:text-accent" href="https://people.php.net/{{ $voter['name'] }}">{{ $voter['name'] }}</a></td>
                                        <td class="pr-4 py-0.5 text-ink-soft">{{ implode(', ', $voter['choices']) }}</td>
                                        <td class="py-0.5 text-muted">{{ $voter['votedAt'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </details>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    <section class="mb-10">
        <h2 class="text-lg font-semibold mb-4">Discussions on the mailing list</h2>
        @forelse($threads as $thread)
            <div wire:key="thread-{{ $thread->number }}" class="py-2.5 border-b border-line last:border-b-0">
                <a class="text-ink hover:text-accent" href="/message/{{ $thread->number }}">{{ $thread->subject }}</a>
                <div class="text-xs text-muted">
                    <time datetime="{{ $thread->date->toAtomString() }}">{{ $thread->date->format('M j, Y') }}</time>
                    @if($thread->fromName) by {{ $thread->fromName }} @endif
                    · {{ $thread->emailCount }} {{ Str::plural('message', $thread->emailCount) }}
                </div>
            </div>
        @empty
            <p class="text-sm text-muted">
                No discussion found for this RFC yet.
                <a class="underline underline-offset-2 hover:text-ink" href="/search?q={{ urlencode($rfc->title) }}">Search the mailing list</a>
            </p>
        @endforelse
    </section>

    <section>
        <h2 class="text-lg font-semibold mb-1">RFC text</h2>
        <p class="text-xs text-muted mb-4">
            Copied from <a class="underline" href="{{ $rfc->wikiUrl() }}">wiki.php.net</a>@if($rfc->fetchedAt), last synchronized {{ $rfc->fetchedAt->format('M j, Y H:i') }} UTC @endif
        </p>
        @if($contentHtml !== null)
            <div class="rfc-content text-sm sm:text-base">
                {!! $contentHtml !!}
            </div>
        @else
            <p class="text-sm text-muted">The text of this RFC has not been imported yet: read it on <a class="underline" href="{{ $rfc->wikiUrl() }}">wiki.php.net</a>.</p>
        @endif
    </section>
</div>
