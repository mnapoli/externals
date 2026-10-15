<?php

use App\Services\Rfc\RfcHighlights;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $highlights = app(RfcHighlights::class);

        return [
            'voting' => $highlights->voting(),
            'inDiscussion' => $highlights->inDiscussion(5),
        ];
    }
}; ?>

<div class="flex flex-col lg:flex-row gap-6 lg:gap-8 items-start">
    <section id="threads" class="w-full lg:flex-1 min-w-0" aria-labelledby="latest-title">
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
            <h1 id="latest-title" class="text-2xl font-bold tracking-tight">Latest threads</h1>
            <p class="text-sm text-muted">Opening PHP's <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="https://news-web.php.net/php.internals" target="_blank" rel="noreferrer noopener">#internals</a> mailing list to the outside.</p>
        </div>
        <livewire:thread-list mode="latest" />
    </section>

    <aside class="w-full lg:w-80 shrink-0 flex flex-col gap-4 lg:sticky lg:top-20" aria-labelledby="rfcs-title">
        <div class="flex items-baseline justify-between gap-2 -mb-1">
            <h2 id="rfcs-title" class="text-2xl font-bold tracking-tight">RFCs</h2>
            <a href="/rfc" class="text-sm text-muted hover:text-ink">All RFCs →</a>
        </div>
        @if($voting->isNotEmpty())
            <section class="bg-surface border border-line rounded-lg overflow-hidden" aria-labelledby="voting-title">
                <div class="flex justify-between items-center px-4 py-2.5 border-b border-line">
                    <h3 id="voting-title" class="label text-ink-soft">Voting now</h3>
                    <span class="font-mono text-xs text-accent-strong">{{ $voting->count() }}</span>
                </div>
                @foreach($voting as $rfc)
                    @php($poll = $rfc->polls->first())
                    @php($closesAt = \App\Services\Rfc\RfcHighlights::closesAt($poll))
                    @php($yesShare = $poll ? \App\Services\Rfc\RfcHighlights::yesShare($poll) : null)
                    <div class="px-4 py-3 border-b border-line last:border-b-0">
                        <a href="/rfc/{{ $rfc->slug }}" class="block font-semibold text-sm text-ink hover:text-accent">{{ $rfc->title }}</a>
                        @if($closesAt)
                            <span class="block mt-0.5 font-mono text-xs text-muted" title="{{ $poll->closesAt }}">{{ $closesAt->isFuture() ? 'Closes in ' . $closesAt->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) : 'Closed ' . $closesAt->diffForHumans() }}</span>
                        @endif
                        @if($yesShare !== null)
                            <span class="relative block mt-2 h-1.5 rounded-full bg-subtle" role="img" aria-label="{{ $poll->summary() }}, {{ round($yesShare * 100) }}% yes, 2/3 needed">
                                <span class="absolute inset-y-0 left-0 rounded-full {{ $yesShare >= 2 / 3 ? 'bg-pass' : 'bg-fail' }}" style="width: {{ round($yesShare * 100, 1) }}%"></span>
                                <span class="absolute -inset-y-0.5 left-2/3 w-0.5 bg-ink" aria-hidden="true"></span>
                            </span>
                            <span class="mt-1.5 flex justify-between font-mono text-xs text-ink-soft">
                                <span>{{ $poll->summary() }}</span>
                                <span class="{{ $yesShare >= 2 / 3 ? 'text-pass' : 'text-fail' }}">{{ $yesShare >= 2 / 3 ? 'passing' : 'failing' }}</span>
                            </span>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        @if($inDiscussion->isNotEmpty())
            <section class="bg-surface border border-line rounded-lg overflow-hidden" aria-labelledby="discussion-title">
                <div class="flex justify-between items-center px-4 py-2.5 border-b border-line">
                    <h3 id="discussion-title" class="label text-ink-soft">Under discussion</h3>
                </div>
                @foreach($inDiscussion as ['rfc' => $rfc, 'activity' => $activity])
                    <div class="px-4 py-3 border-b border-line last:border-b-0">
                        <a href="/rfc/{{ $rfc->slug }}" class="block font-semibold text-sm text-ink hover:text-accent">{{ $rfc->title }}</a>
                        <span class="block mt-0.5 font-mono text-xs text-muted">
                            {{ $activity->threadCount }} {{ Str::plural('thread', $activity->threadCount) }}
                            · <time datetime="{{ $activity->lastActivity->toAtomString() }}" title="{{ $activity->lastActivity->format('D, M j, Y \a\t H:i T') }}">{{ $activity->lastActivity->diffForHumans() }}</time>
                        </span>
                    </div>
                @endforeach
            </section>
        @endif

        @auth
            <section class="hidden lg:block bg-surface border border-line rounded-lg px-4 py-3 text-sm text-ink-soft" aria-labelledby="keyboard-title">
                <h2 id="keyboard-title" class="label mb-2">Keyboard</h2>
                <dl class="grid grid-cols-[4rem_minmax(0,1fr)] gap-y-1.5 items-center">
                    <dt><span class="kbd">/</span></dt><dd>Search</dd>
                    <dt><span class="kbd">n</span></dt><dd>Next unread message, in a thread</dd>
                </dl>
            </section>
        @endauth
    </aside>
</div>
