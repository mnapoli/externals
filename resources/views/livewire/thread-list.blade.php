<?php

use App\Actions\CastVote;
use App\Models\Email;
use App\Models\Vote;
use App\Services\Email\ThreadQuery;
use App\Services\Rfc\RfcThreads;
use App\Services\Rfc\ThreadRfcs;
use App\Support\Email\ThreadSummary;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new class extends Component {
    /** Either "latest" or "top". */
    public string $mode = 'latest';

    #[Url]
    public int $page = 1;

    public function vote(int $number, int $value): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return;
        }
        if (! in_array($value, [1, -1], true)) {
            return;
        }

        $userId = (int) auth()->id();

        // Clicking the arrow that is already active removes the vote.
        $current = (int) (Vote::query()
            ->where('userId', $userId)
            ->where('emailNumber', $number)
            ->value('value') ?? 0);
        $newValue = $current === $value ? 0 : $value;

        app(CastVote::class)->handle($userId, $number, $newValue);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $user = auth()->user();

        if ($this->mode === 'top') {
            $threads = app(ThreadQuery::class)->findTopThreads(1, $user);

            return [
                'threads' => $threads,
                'rfcs' => $this->rfcsOf($threads),
                'pageCount' => 1,
            ];
        }

        $threadCount = Cache::remember(
            'stats.threadCount',
            now()->addMinutes(5),
            fn() => Email::where('isThreadRoot', true)->count(),
        );

        $threads = app(ThreadQuery::class)->findLatestThreads($this->page, $user);

        return [
            'threads' => $threads,
            'rfcs' => $this->rfcsOf($threads),
            'pageCount' => (int) ceil($threadCount / 20),
        ];
    }

    /**
     * @param  ThreadSummary[]  $threads
     * @return array<int, ThreadRfcs> Keyed by thread number
     */
    private function rfcsOf(array $threads): array
    {
        $subjects = [];
        foreach ($threads as $thread) {
            $subjects[$thread->number] = $thread->subject;
        }

        return app(RfcThreads::class)->rfcLinksOfThreads($subjects);
    }
}; ?>

<div class="thread-list">
    @if($threads === [])
        <p class="px-4 py-10 text-center text-muted bg-surface border border-line rounded-lg">
            @if($mode === 'top')
                No thread got upvotes in the last month. <a href="/" class="text-accent-strong underline underline-offset-2">Browse the latest threads</a>.
            @else
                No threads yet.
            @endif
        </p>
    @else
    <ol class="bg-surface border border-line rounded-lg overflow-hidden">
        @foreach($threads as $thread)
            @php($subject = \App\Support\Email\SubjectTags::parse($thread->subject))
            @php($primaryRfc = ($rfcs[$thread->number] ?? null)?->primary)
            @php($unread = auth()->check() && ! $thread->isRead)
            <li wire:key="thread-{{ $thread->number }}"
                class="grid grid-cols-[minmax(0,1fr)_auto] sm:grid-cols-[3rem_minmax(0,1fr)_auto] gap-x-3 sm:gap-x-4 items-center px-3 sm:px-4 py-3 border-b border-line last:border-b-0 hover:bg-[#fafaff]">
                <div class="row-start-1 col-start-2 sm:col-start-1 flex flex-col items-center font-mono text-[13px] text-ink-soft" role="group" aria-label="Thread vote">
                    <button type="button" wire:click="vote({{ $thread->number }}, 1)" aria-pressed="{{ $thread->userVote === 1 ? 'true' : 'false' }}" aria-label="Upvote"
                            class="w-9 h-6 sm:h-5 rounded text-[11px] cursor-pointer {{ $thread->userVote === 1 ? 'bg-accent-soft text-accent-strong' : 'text-faint hover:bg-subtle hover:text-ink' }}">▲</button>
                    <span class="leading-tight {{ $thread->userVote ? 'text-accent-strong font-semibold' : '' }}">{{ $thread->votes }}</span>
                    <button type="button" wire:click="vote({{ $thread->number }}, -1)" aria-pressed="{{ $thread->userVote === -1 ? 'true' : 'false' }}" aria-label="Downvote"
                            class="w-9 h-6 sm:h-5 rounded text-[11px] cursor-pointer {{ $thread->userVote === -1 ? 'bg-accent-soft text-accent-strong' : 'text-faint hover:bg-subtle hover:text-ink' }}">▼</button>
                </div>
                <div class="row-start-1 col-start-1 sm:col-start-2 min-w-0">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        @if($primaryRfc)
                            <a href="/rfc/{{ $primaryRfc->slug }}" title="RFC: {{ $primaryRfc->title }}"
                               class="label px-1.5 py-0.5 rounded whitespace-nowrap {{ $primaryRfc->status->badgeClasses() }}">RFC · {{ $primaryRfc->status->badgeLabel() }}</a>
                        @endif
                        @foreach($primaryRfc === null ? $subject->tags : $subject->tagsExcept('RFC', 'Discussion') as $tag)
                            <span class="label px-1.5 py-0.5 rounded whitespace-nowrap bg-subtle text-ink-soft">{{ $tag }}</span>
                        @endforeach
                        <a href="/message/{{ $thread->number }}"
                           class="text-[15px] leading-snug {{ $unread ? 'font-semibold text-ink' : 'font-medium' }} {{ $thread->votes < -1 ? 'text-faint' : 'text-ink' }} hover:text-accent">
                            @if($unread)<span class="inline-block w-[7px] h-[7px] mr-1.5 align-[2px] rounded-full bg-accent" aria-hidden="true"></span><span class="sr-only">Unread: </span>@endif{{ $subject->title }}
                        </a>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[13px] text-muted">
                        @if($thread->fromName)<span>{{ $thread->fromName }}</span>@endif
                        <span class="sm:hidden font-mono text-xs">{{ $thread->emailCount }} {{ Str::plural('msg', $thread->emailCount) }}@if($thread->newCount > 0) · <span class="text-accent-strong font-semibold">{{ $thread->newCount }} new</span>@endif</span>
                        <a href="/message/{{ $thread->number }}" class="sm:hidden font-mono text-xs hover:text-ink" title="Last message {{ $thread->lastUpdate->format('D, M j, Y \a\t H:i T') }}"><time datetime="{{ $thread->lastUpdate->toAtomString() }}">{{ $thread->lastUpdate->diffForHumans(short: true) }}</time></a>
                        @if($thread->votes < -1)<span class="text-xs">low score</span>@endif
                    </div>
                </div>
                <div class="hidden sm:flex flex-col items-end gap-1 font-mono text-xs text-muted whitespace-nowrap">
                    <span>{{ $thread->emailCount }} {{ Str::plural('msg', $thread->emailCount) }}@if($thread->newCount > 0) · <span class="text-accent-strong font-semibold">{{ $thread->newCount }} new</span>@endif</span>
                    <a href="/message/{{ $thread->number }}" class="hover:text-ink" title="Last message {{ $thread->lastUpdate->format('D, M j, Y \a\t H:i T') }}"><time datetime="{{ $thread->lastUpdate->toAtomString() }}">{{ $thread->lastUpdate->diffForHumans() }}</time></a>
                </div>
            </li>
        @endforeach
    </ol>
    @endif

    @if($mode === 'latest' && $pageCount > 1)
        <nav aria-label="Pagination" class="mt-4 flex flex-wrap items-center justify-between gap-3 font-mono text-[13px]">
            <span class="text-muted">Page {{ $page }} of {{ number_format($pageCount) }}</span>
            <div class="flex flex-wrap gap-1.5">
                @php($pageLinks = array_values(array_unique(array_filter([1, $page - 2, $page - 1, $page, $page + 1, $page + 2, $pageCount], fn(int $i) => $i >= 1 && $i <= $pageCount))))
                @if($page > 1)
                    <a href="/?page={{ $page - 1 }}" wire:click.prevent="$set('page', {{ $page - 1 }})" rel="prev"
                       class="px-3 py-2 rounded-md border border-line-strong bg-surface hover:border-accent-line">← Newer</a>
                @endif
                @foreach($pageLinks as $i => $pageLink)
                    @if($i > 0 && $pageLink > $pageLinks[$i - 1] + 1)
                        <span class="px-1 py-2 text-faint" aria-hidden="true">…</span>
                    @endif
                    @if($pageLink === $page)
                        <span aria-current="page" class="px-3 py-2 rounded-md border border-accent-line bg-accent-soft text-accent-strong font-semibold">{{ $pageLink }}</span>
                    @else
                        <a href="/?page={{ $pageLink }}" wire:click.prevent="$set('page', {{ $pageLink }})"
                           class="px-3 py-2 rounded-md border border-line-strong bg-surface hover:border-accent-line">{{ $pageLink }}</a>
                    @endif
                @endforeach
                @if($page < $pageCount)
                    <a href="/?page={{ $page + 1 }}" wire:click.prevent="$set('page', {{ $page + 1 }})" rel="next"
                       class="px-3 py-2 rounded-md border border-line-strong bg-surface hover:border-accent-line">Older →</a>
                @endif
            </div>
        </nav>
    @endif
</div>
