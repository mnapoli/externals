<?php

use App\Actions\MarkEmailAsRead;
use App\Exceptions\NotFoundException;
use App\Models\Email;
use App\Models\Vote;
use App\Services\Email\ReactionQuery;
use App\Services\Email\ThreadQuery;
use App\Services\Rfc\RfcThreads;
use App\Support\Email\SubjectTags;
use App\Support\Email\ThreadItem;
use App\Support\Email\ThreadStructuredData;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public int $number = 0;

    public string $subject = '';

    /** @var \App\Support\Email\ThreadItem[] */
    protected array $thread = [];

    public function mount(int $number, ThreadQuery $threads): mixed
    {
        $email = Email::where('number', $number)->first();
        if (! $email) {
            throw new NotFoundException("Email $number was not found");
        }

        if (! $email->isThreadRoot()) {
            $root = Email::find($email->threadId);
            if ($root) {
                return $this->redirect("/message/{$root->number}#$number");
            }
            // Root message not found — render the thread from this URL anyway
        }

        $user = auth()->user();
        // Build the thread view BEFORE marking the thread as read
        $this->thread = $threads->getThreadView($email, $user);
        $this->number = $number;
        $this->subject = $email->subject;

        if ($user) {
            app(MarkEmailAsRead::class)->handle($email, $user);
        }

        return null;
    }

    public function rendering(View $view): void
    {
        $view->title($this->subject . ' - Externals');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $messages = array_merge(...array_map(fn(ThreadItem $item) => $item->flatten(), $this->thread));
        $userId = auth()->id();

        return [
            'thread' => $this->thread,
            'messages' => $messages,
            'subjectTags' => SubjectTags::parse($this->subject),
            'participantCount' => count(array_unique(array_map(fn(ThreadItem $item) => mb_strtolower($item->email->from->getNameOrEmail()), $messages))),
            'unreadCount' => $userId !== null ? count(array_filter($messages, fn(ThreadItem $item) => ! $item->email->isRead)) : 0,
            'reactions' => app(ReactionQuery::class)->forEmails(array_map(fn(ThreadItem $item) => $item->email->number, $messages), $userId !== null ? (int) $userId : null),
            'votes' => (int) Vote::where('emailNumber', $this->number)->sum('value'),
            'userVote' => $userId !== null ? (int) (Vote::where('userId', $userId)->where('emailNumber', $this->number)->value('value') ?? 0) : 0,
            'structuredData' => ThreadStructuredData::build($this->thread, $this->number, $this->subject),
            'rfcs' => app(RfcThreads::class)->rfcsOf($this->number),
        ];
    }
}; ?>

@push('head')
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR) !!}</script>
    <link rel="alternate" type="text/markdown" href="/message/{{ $number }}.md" title="Thread as Markdown">
@endpush

@php($showOutline = count($messages) >= 4)
@php($first = $messages[0]->email ?? null)
@php($last = $messages === [] ? null : max(array_map(fn($item) => $item->email->date, $messages)))
<div x-data="{
        unread: [],
        all: [],
        cursor: 0,
        current: null,
        filter: @js($unreadCount > 0 ? 'unread' : 'all'),
        outlineOpen: false,
        init() {
            this.all = [...this.$root.querySelectorAll('article[data-message]')].map(a => a.id);
            this.unread = [...this.$root.querySelectorAll('article.unread')].map(a => a.id);
            const onScroll = () => {
                let current = this.all[0] ?? null;
                for (const id of this.all) {
                    const el = document.getElementById(id);
                    if (el && el.offsetParent !== null && el.getBoundingClientRect().top < 140) current = id;
                }
                this.current = current;
            };
            let ticking = false;
            window.addEventListener('scroll', () => { if (! ticking) { ticking = true; requestAnimationFrame(() => { onScroll(); ticking = false }) } }, { passive: true });
            onScroll();
        },
        go(id) {
            if (! id) return;
            this.$dispatch('thread-reveal', id);
            this.outlineOpen = false;
            this.$nextTick(() => { window.location.hash = '#' + id });
        },
        nextUnread() {
            if (this.cursor >= this.unread.length) this.cursor = 0;
            this.go(this.unread[this.cursor++]);
        },
        step(offset) {
            const index = this.all.indexOf(this.current);
            this.go(this.all[Math.min(Math.max(index + offset, 0), this.all.length - 1)]);
        },
     }"
     @keydown.window="if (['INPUT', 'TEXTAREA'].includes($event.target.tagName) || $event.metaKey || $event.ctrlKey || $event.altKey) return;
        if ($event.key === 'n') nextUnread();
        if ($event.key === 'j') step(1);
        if ($event.key === 'k') step(-1);"
     class="flex flex-col lg:flex-row gap-6 lg:gap-8 items-start pb-20 lg:pb-0">

    @if($showOutline)
        {{-- Outline: sticky column on large screens, bottom sheet on small ones --}}
        <div x-show="outlineOpen" x-cloak x-transition.opacity @click="outlineOpen = false" class="lg:hidden fixed inset-0 z-40 bg-ink/45" aria-hidden="true"></div>
        <nav aria-label="Thread outline"
             :class="outlineOpen && 'flex!'"
             @keydown.escape="outlineOpen = false"
             class="hidden lg:flex flex-col fixed lg:sticky inset-x-0 bottom-0 lg:top-20 z-50 lg:z-auto max-h-[75vh] lg:max-h-[calc(100vh-6rem)] w-full lg:w-72 shrink-0 order-last lg:order-first rounded-t-2xl lg:rounded-lg border border-line bg-surface shadow-2xl lg:shadow-none">
            <div class="flex items-center justify-between gap-2 px-4 lg:px-3 pt-3 pb-2">
                <h2 class="label text-ink-soft">Outline</h2>
                <span class="font-mono text-xs text-muted">{{ count($messages) }}@if($unreadCount > 0) · <span class="text-accent-strong">{{ $unreadCount }} unread</span>@endif</span>
                <button type="button" @click="outlineOpen = false" class="lg:hidden ml-auto inline-flex items-center justify-center w-10 h-10 rounded-md hover:bg-subtle" aria-label="Close the outline">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            @if($unreadCount > 0)
                <div role="group" aria-label="Outline filter" class="mx-4 lg:mx-3 mb-2 flex rounded-md bg-subtle p-0.5 text-xs">
                    <button type="button" @click="filter = 'all'" :aria-pressed="filter === 'all'" class="flex-1 rounded py-1.5 lg:py-1 cursor-pointer" :class="filter === 'all' ? 'bg-surface font-semibold shadow-sm' : 'text-ink-soft'">All</button>
                    <button type="button" @click="filter = 'unread'" :aria-pressed="filter === 'unread'" class="flex-1 rounded py-1.5 lg:py-1 cursor-pointer" :class="filter === 'unread' ? 'bg-surface font-semibold shadow-sm' : 'text-ink-soft'">Unread in context</button>
                </div>
            @endif
            <ol class="overflow-y-auto overscroll-contain px-2 pb-3">
                @foreach($messages as $message)
                    @php($messageUnread = auth()->check() && ! $message->email->isRead)
                    @php($inContext = $messageUnread || (auth()->check() && $message->unreadDescendantCount() > 0))
                    @php($depth = $message->depth())
                    <li @if(! $inContext) x-show="filter === 'all'" @endif>
                        <a href="#{{ $message->email->number }}" @click.prevent="go('{{ $message->email->number }}')"
                           :class="current === '{{ $message->email->number }}' && 'bg-accent-soft'"
                           @class([
                               'flex items-center gap-1.5 min-h-11 lg:min-h-0 px-2 py-1 rounded text-[13px] whitespace-nowrap overflow-hidden hover:bg-subtle',
                               'font-semibold text-ink' => $messageUnread,
                               'text-faint' => auth()->check() && ! $messageUnread,
                               'text-ink-soft' => ! auth()->check(),
                           ])>
                            @for($i = 0; $i < min($depth, 6); $i++)
                                <span class="w-2 h-5 shrink-0 border-l border-line-strong" aria-hidden="true"></span>
                            @endfor
                            @if($depth > 6)
                                <span class="font-mono text-[10px] text-faint shrink-0" title="Depth {{ $depth }}">+{{ $depth - 6 }}</span>
                            @endif
                            @if($messageUnread)
                                <span class="w-[7px] h-[7px] shrink-0 rounded-full bg-accent" aria-hidden="true"></span><span class="sr-only">Unread:</span>
                            @endif
                            <span class="truncate">{{ $message->email->from->getNameOrEmail() }}</span>
                            <time class="ml-auto pl-2 font-mono text-[11px] font-normal text-faint" datetime="{{ $message->email->date->format(DATE_ATOM) }}">{{ $message->email->date->format($first && $message->email->date->format('Ymd') === $first->date->format('Ymd') ? 'H:i' : 'M j') }}</time>
                        </a>
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="w-full min-w-0 lg:flex-1 max-w-4xl">
        <a href="/" class="text-sm text-muted hover:text-ink">← Latest</a>
        <div class="mt-2 flex gap-3 sm:gap-4 items-start">
            <livewire:thread-vote :number="$number" :votes="$votes" :user-vote="$userVote" />
            <div class="min-w-0 flex-1">
                @php($tags = $rfcs->isEmpty() ? $subjectTags->tags : $subjectTags->tagsExcept('RFC', 'Discussion'))
                @if($tags !== [])
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($tags as $tag)
                            <span class="label px-1.5 py-0.5 rounded bg-subtle text-ink-soft">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
                <h1 class="mt-1.5 text-2xl sm:text-3xl font-bold tracking-tight leading-tight break-words">{{ $subjectTags->title }}</h1>
                <p class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 font-mono text-xs text-muted">
                    <span>{{ count($messages) }} {{ Str::plural('message', count($messages)) }}</span>
                    <span>{{ $participantCount }} {{ Str::plural('participant', $participantCount) }}</span>
                    @if($first && $last)
                        <span>{{ $first->date->format('M j, Y') }}@if($last->format('Ymd') !== $first->date->format('Ymd')) → {{ $last->format('M j, Y') }}@endif</span>
                    @endif
                </p>
            </div>
        </div>

        @if($unreadCount > 0 || count($messages) > 1)
            <div class="mt-5 flex flex-wrap gap-2">
                @if($unreadCount > 0)
                    <button type="button" @click="cursor = 0; nextUnread()"
                            class="inline-flex items-center gap-2 h-9 px-3 rounded-md bg-accent text-white text-sm font-semibold hover:bg-accent-strong cursor-pointer">
                        Jump to first unread
                        <span class="font-mono text-xs bg-white/20 rounded px-1.5">{{ $unreadCount }}</span>
                        <span class="hidden sm:inline kbd bg-transparent text-white border-white/40">n</span>
                    </button>
                    <button type="button" @click="$dispatch('thread-collapse-read')" class="h-9 px-3 rounded-md border border-line-strong bg-surface text-sm hover:border-accent-line cursor-pointer">Collapse read</button>
                @endif
                @if(count($messages) > 1)
                    <button type="button" @click="$dispatch('thread-expand-all')" class="h-9 px-3 rounded-md border border-line-strong bg-surface text-sm hover:border-accent-line cursor-pointer">Expand all</button>
                @endif
            </div>
        @endif

        @foreach($rfcs as $rfc)
            @php($poll = $rfc->polls->first())
            <a href="/rfc/{{ $rfc->slug }}" class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg border border-line bg-surface hover:border-accent-line px-4 py-3 text-sm">
                <span class="label text-muted">RFC</span>
                <span class="font-semibold">{{ $rfc->title }}</span>
                <span class="label px-1.5 py-0.5 rounded {{ $rfc->status->badgeClasses() }}">{{ $rfc->statusLabel() }}</span>
                @if($poll?->hasVotes())
                    <span class="sm:ml-auto font-mono text-xs text-muted">Official vote: {{ $poll->summary() }}@if(! $poll->isClosed) · open @endif</span>
                @endif
            </a>
        @endforeach

        <div class="mt-6 flex flex-col gap-3">
            @foreach($thread as $item)
                <x-thread-item :item="$item" :reactions="$reactions" />
            @endforeach
        </div>
    </div>

    {{-- Bottom bar on small screens --}}
    @if($showOutline || $unreadCount > 0)
        <div class="lg:hidden fixed inset-x-0 bottom-0 z-30 flex items-center gap-2 border-t border-line bg-surface/95 backdrop-blur px-3 pt-2 pb-[max(0.5rem,env(safe-area-inset-bottom))]">
            @if($showOutline)
                <button type="button" @click="outlineOpen = true" :aria-expanded="outlineOpen"
                        class="inline-flex items-center gap-2 h-11 px-3.5 rounded-md border border-line-strong text-[15px] font-semibold cursor-pointer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M8 12h12M12 18h8"/></svg>
                    Outline
                </button>
            @endif
            @if($unreadCount > 0)
                <button type="button" @click="nextUnread()" class="ml-auto inline-flex items-center gap-2 h-11 px-3.5 rounded-md bg-accent text-white text-[15px] font-semibold cursor-pointer">
                    Next unread <span class="font-mono text-xs bg-white/20 rounded px-1.5">{{ $unreadCount }}</span>
                </button>
            @endif
        </div>
    @endif
</div>
