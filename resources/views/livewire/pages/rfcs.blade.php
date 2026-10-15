<?php

use App\Models\Rfc;
use App\Models\RfcVote;
use App\Services\Email\AvatarUrls;
use App\Services\Rfc\RfcList;
use App\Services\Rfc\RfcThreads;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    /** RFCs shown per section before "Show more": rendering all ~940 RFCs is slow. */
    public const int SECTION_PREVIEW = 10;

    /** Filter on the title or slug. */
    #[Url(except: '')]
    public string $q = '';

    /** @var list<string> Titles of the sections showing all their RFCs */
    public array $expanded = [];

    /** @var list<string> Titles of the sections closed by default that the visitor opened */
    public array $opened = [];

    public function expand(string $section): void
    {
        $this->expanded[] = $section;
    }

    /**
     * Sections closed by default are only rendered once opened: the archives are most of the ~940 RFCs.
     */
    public function open(string $section): void
    {
        if (! in_array($section, $this->opened, true)) {
            $this->opened[] = $section;
        }
    }

    public function rendering(View $view): void
    {
        $view->title('PHP RFCs - Externals');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $sections = app(RfcList::class)->sections();

        $q = mb_strtolower(mb_trim($this->q));
        if ($q !== '') {
            // All the matches, in open sections
            $sections = array_values(array_filter(array_map(function (array $section) use ($q) {
                $section['rfcs'] = array_values(array_filter(
                    $section['rfcs'],
                    fn(Rfc $rfc) => str_contains(mb_strtolower($rfc->title . ' ' . $rfc->slug), $q),
                ));
                $section['open'] = true;

                return $section;
            }, $sections), fn(array $section) => $section['rfcs'] !== []));
        }

        // Anchors and subsections ("PHP 8.5") of the sections, the RFCs they show, and the navigation: one entry per status
        $sections = array_map(function (array $section) use ($q) {
            $loaded = $q !== '' || $section['open'] || in_array($section['title'], $this->opened, true);
            $showAll = $q !== '' || in_array($section['title'], $this->expanded, true);
            $shown = ! $loaded ? [] : ($showAll ? $section['rfcs'] : array_slice($section['rfcs'], 0, self::SECTION_PREVIEW));

            return $section + [
                'anchor' => 'section-' . Str::slug($section['title']),
                'subtitle' => str_contains($section['title'], ' — ') ? Str::after($section['title'], ' — ') : null,
                'loaded' => $loaded,
                'shown' => $shown,
                'hiddenCount' => $loaded ? count($section['rfcs']) - count($shown) : 0,
            ];
        }, $sections);
        $nav = [];
        foreach ($sections as $section) {
            $last = array_key_last($nav);
            if ($last === null || $nav[$last]['status'] !== $section['status']) {
                $nav[] = ['status' => $section['status'], 'anchor' => $section['anchor'], 'count' => 0, 'children' => []];
                $last = array_key_last($nav);
            }
            $nav[$last]['count'] += count($section['rfcs']);
            if ($section['subtitle'] !== null) {
                $nav[$last]['children'][] = ['label' => $section['subtitle'], 'anchor' => $section['anchor'], 'count' => count($section['rfcs'])];
            }
        }

        return [
            'sections' => $sections,
            'nav' => $nav,
            'total' => array_sum(array_map(fn(array $section) => count($section['rfcs']), $sections)),
            'avatars' => app(AvatarUrls::class)->of(array_merge(...array_map(fn(array $section) => array_map(fn(Rfc $rfc) => $rfc->authorEmail(), $section['shown']), $sections)), 48),
            'searching' => $q !== '',
            'activity' => app(RfcThreads::class)->activityByRfc(),
            'userVotes' => auth()->check() ? RfcVote::where('userId', auth()->id())->pluck('value', 'rfcId') : collect(),
        ];
    }
}; ?>

@push('head')
    <link rel="alternate" type="text/markdown" href="/rfc.md" title="RFCs as Markdown">
@endpush

<div class="lg:grid lg:grid-cols-[13rem_minmax(0,56rem)] lg:gap-10 xl:gap-14"
     x-data="{
        {{-- Opens the section of the URL, e.g. /rfc#section-inactive --}}
        openFromUrl() {
            const section = location.hash.startsWith('#section-') && document.getElementById(location.hash.slice(1));
            if (section) { section.open = true; section.scrollIntoView() }
        },
     }"
     x-init="openFromUrl()"
     x-on:hashchange.window="openFromUrl()"
     x-on:click="const link = $event.target.closest('a[data-section]'); if (link) document.getElementById(link.dataset.section).open = true">
    {{-- Sections of the index, ~25: jumping to one opens it --}}
    <nav aria-label="Sections" class="hidden lg:block">
        <div class="sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto pb-6">
            <h2 class="label mb-3">Sections</h2>
            <ul class="space-y-0.5 text-sm">
                @foreach($nav as $entry)
                    <li>
                        <a href="#{{ $entry['anchor'] }}" data-section="{{ $entry['anchor'] }}"
                           class="flex items-baseline justify-between gap-3 px-2 py-1 -mx-2 rounded text-ink-soft hover:text-ink hover:bg-subtle">
                            <span class="truncate">{{ $entry['status']->label() }}</span>
                            <span class="font-mono text-xs text-faint">{{ $entry['count'] }}</span>
                        </a>
                        @if($entry['children'] !== [])
                            <ul class="mb-1.5 ml-2 pl-2 border-l border-line">
                                @foreach($entry['children'] as $child)
                                    <li>
                                        <a href="#{{ $child['anchor'] }}" data-section="{{ $child['anchor'] }}"
                                           class="flex items-baseline justify-between gap-3 px-2 py-0.5 rounded text-[13px] text-muted hover:text-ink hover:bg-subtle">
                                            <span class="truncate" title="{{ $child['label'] }}">{{ $child['label'] }}</span>
                                            <span class="font-mono text-xs text-faint">{{ $child['count'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>

    <div class="min-w-0">
        <div class="flex flex-wrap items-baseline gap-x-3 mb-1">
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">PHP RFCs</h1>
            @unless($searching)<span class="font-mono text-sm text-muted">{{ $total }}</span>@endunless
        </div>
        <p class="text-sm text-muted mb-5">
            The RFCs of <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="https://wiki.php.net/rfc">wiki.php.net/rfc</a>, synchronized every hour, with their discussions on the mailing list.
            <span>▲ ▼ is the community opinion on externals.io, not the official vote.</span>
        </p>

        <label class="relative block mb-8">
            <span class="sr-only">Filter RFCs by title</span>
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
            <input type="search" wire:model.live.debounce.250ms="q" placeholder="Filter by title…"
                   class="w-full pl-10 pr-4 h-11 rounded-lg border border-line-strong focus:border-accent-line focus:ring-4 focus:ring-accent-soft bg-surface outline-none">
        </label>

        @if($searching && $sections === [])
            <p class="text-sm text-muted">No RFC matches "{{ $q }}".</p>
        @endif

        @foreach($sections as $section)
            @php($hiddenCount = $section['hiddenCount'])
            @php($sub = $section['subtitle'])
            {{-- wire:ignore.self: re-renders must not close the sections opened by the visitor --}}
            <details id="{{ $section['anchor'] }}" wire:key="section-{{ $searching ? 'search' : 'all' }}-{{ $section['title'] }}" wire:ignore.self
                     x-on:toggle="if ($el.open && ! $el.querySelector('[data-rfcs]')) $wire.open(@js($section['title'])).then(() => { if (location.hash === '#' + $el.id) $el.scrollIntoView() })"
                     class="group/section mb-4 open:mb-8" @if($section['open']) open @endif>
                <summary class="list-none [&::-webkit-details-marker]:hidden flex items-center gap-2.5 mb-3 cursor-pointer select-none rounded focus-visible:outline-2">
                    <svg class="w-3.5 h-3.5 shrink-0 text-faint -rotate-90 group-open/section:rotate-0 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                    <h2 class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                        <span class="label px-1.5 py-0.5 rounded {{ $section['status']->badgeClasses() }}">{{ $section['status']->badgeLabel() }}</span>
                        @if($sub)<span class="text-base font-semibold text-ink">{{ $sub }}</span>@endif
                    </h2>
                    <span class="font-mono text-xs text-muted">{{ count($section['rfcs']) }}</span>
                </summary>
                <div class="bg-surface border border-line rounded-lg overflow-hidden">
                    @unless($section['loaded'])
                        <p class="px-4 py-3 font-mono text-xs text-muted">Loading…</p>
                    @endunless
                    <ul @if($section['loaded']) data-rfcs @endif>
                        @foreach($section['shown'] as $rfc)
                            @php($rfcActivity = $activity->get($rfc->slug))
                            @php($poll = $rfc->status === \App\Services\Rfc\RfcStatus::Voting ? $rfc->polls->first() : null)
                            @php($avatar = $avatars[$rfc->authorEmail() ?? ''] ?? null)
                            <li wire:key="rfc-{{ $rfc->slug }}" class="grid grid-cols-[1.75rem_minmax(0,1fr)] sm:grid-cols-[1.75rem_minmax(0,1fr)_auto] gap-x-3 items-start px-3 sm:px-4 py-3 border-b border-line last:border-b-0 hover:bg-[#fafaff]">
                                <span class="row-span-2 sm:row-span-1 relative mt-0.5 w-7 h-7 shrink-0 rounded-full bg-[#e4e4ea] text-[10px] font-semibold text-ink-soft flex items-center justify-center select-none"
                                      title="{{ $rfc->authors }}">
                                    <span aria-hidden="true">{{ $rfc->authorInitials() }}</span>
                                    @if($avatar)
                                        <img src="{{ $avatar }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full rounded-full object-cover">
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <a href="/rfc/{{ $rfc->slug }}" class="font-medium text-[15px] leading-snug text-ink hover:text-accent [overflow-wrap:anywhere]">{{ $rfc->title }}</a>
                                    @if($rfc->authorName() !== '' || $rfcActivity)
                                        <p class="mt-0.5 text-[13px] text-muted truncate">
                                            {{ $rfc->authorName() }}
                                            @if($rfcActivity)
                                                <span class="font-mono text-xs">@if($rfc->authorName() !== '') · @endif{{ $rfcActivity->threadCount }} {{ Str::plural('thread', $rfcActivity->threadCount) }}@if($rfcActivity->lastActivity) · <time datetime="{{ $rfcActivity->lastActivity->toAtomString() }}" title="{{ $rfcActivity->lastActivity->format('D, M j, Y \a\t H:i T') }}">{{ $rfcActivity->lastActivity->diffForHumans() }}</time>@endif</span>
                                            @endif
                                        </p>
                                    @endif
                                    @if($poll)
                                        <x-rfc-poll :poll="$poll" class="mt-2 max-w-sm" />
                                    @endif
                                </div>
                                @if($rfc->status->acceptsCommunityVotes() || $rfc->upvotes || $rfc->downvotes)
                                    <div class="col-start-2 sm:col-start-3 sm:row-start-1 -ml-1.5 sm:ml-0 mt-1 sm:mt-0 sm:-my-0.5">
                                        <livewire:rfc-vote :rfc-id="$rfc->id" :can-vote="$rfc->status->acceptsCommunityVotes()"
                                                           :upvotes="(int) $rfc->upvotes" :downvotes="(int) $rfc->downvotes"
                                                           :user-vote="(int) $userVotes->get($rfc->id, 0)"
                                                           :key="'vote-' . $rfc->id" />
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if($hiddenCount > 0)
                        <button type="button" wire:click="expand(@js($section['title']))" wire:loading.attr="disabled"
                                class="w-full px-4 py-2.5 border-t border-line text-left font-mono text-xs text-muted hover:text-ink hover:bg-subtle cursor-pointer">
                            <span wire:loading.remove wire:target="expand(@js($section['title']))">Show {{ $hiddenCount }} more</span>
                            <span wire:loading wire:target="expand(@js($section['title']))">Loading…</span>
                        </button>
                    @endif
                </div>
            </details>
        @endforeach
    </div>
</div>
