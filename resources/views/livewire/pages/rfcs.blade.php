<?php

use App\Models\Rfc;
use App\Models\RfcVote;
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

    public function expand(string $section): void
    {
        $this->expanded[] = $section;
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

        return [
            'sections' => $sections,
            'searching' => $q !== '',
            'activity' => app(RfcThreads::class)->activityByRfc(),
            'userVotes' => auth()->check() ? RfcVote::where('userId', auth()->id())->pluck('value', 'rfcId') : collect(),
        ];
    }
}; ?>

@push('head')
    <link rel="alternate" type="text/markdown" href="/rfc.md" title="RFCs as Markdown">
@endpush

<div>
    <h1 class="text-xl sm:text-3xl font-light mb-2">
        PHP RFCs
        <a href="/rfc.md" type="text/markdown" class="ml-2 align-middle text-xs font-normal text-gray-400 hover:text-gray-600" title="This list as Markdown, for AI agents">View as Markdown</a>
    </h1>
    <p class="text-sm text-gray-500 mb-6">
        The RFCs of <a class="underline" href="https://wiki.php.net/rfc">wiki.php.net/rfc</a>, synchronized every hour, with their discussions on the mailing list.
    </p>

    <input type="search" wire:model.live.debounce.250ms="q" placeholder="Filter by title…" aria-label="Filter RFCs by title"
           class="w-full mb-8 px-4 py-2 rounded border border-gray-300 focus:border-gray-400 bg-white">

    @if($searching && $sections === [])
        <p class="text-sm text-gray-500">No RFC matches "{{ $q }}".</p>
    @endif

    @foreach($sections as $section)
        @php($showAll = $searching || in_array($section['title'], $expanded, true))
        @php($hiddenCount = $showAll ? 0 : max(0, count($section['rfcs']) - self::SECTION_PREVIEW))
        {{-- wire:ignore.self: re-renders must not close the sections opened by the visitor --}}
        <details wire:key="section-{{ $searching ? 'search' : 'all' }}-{{ $section['title'] }}" wire:ignore.self
                 class="mb-6" @if($section['open']) open @endif>
            <summary class="cursor-pointer select-none text-lg text-gray-700 mb-2">
                {{ $section['title'] }}
                <span class="text-sm text-gray-400">({{ count($section['rfcs']) }})</span>
            </summary>
            <ul>
                @foreach($showAll ? $section['rfcs'] : array_slice($section['rfcs'], 0, self::SECTION_PREVIEW) as $rfc)
                    @php($rfcActivity = $activity->get($rfc->slug))
                    @php($poll = $rfc->status === \App\Services\Rfc\RfcStatus::Voting ? $rfc->polls->first() : null)
                    <li wire:key="rfc-{{ $rfc->slug }}"
                        class="py-2 border-b border-gray-100 flex items-baseline gap-3">
                        @if($rfc->status->acceptsCommunityVotes() || $rfc->upvotes || $rfc->downvotes)
                            <livewire:rfc-vote :rfc-id="$rfc->id" :can-vote="$rfc->status->acceptsCommunityVotes()"
                                               :upvotes="(int) $rfc->upvotes" :downvotes="(int) $rfc->downvotes"
                                               :user-vote="(int) $userVotes->get($rfc->id, 0)"
                                               :key="'vote-' . $rfc->id" />
                        @endif
                        <div class="relative self-center w-6 h-6 shrink-0 rounded-full bg-gray-100 text-[10px] leading-none text-gray-500 flex items-center justify-center select-none"
                             title="{{ $rfc->authors }}">
                            {{ $rfc->authorInitials() }}
                            @if($rfc->authorAvatarUrl())
                                <img src="{{ $rfc->authorAvatarUrl() }}" alt="" loading="lazy" class="absolute inset-0 w-6 h-6 rounded-full">
                            @endif
                        </div>
                        <div class="flex-grow min-w-0 sm:flex sm:items-baseline sm:gap-4">
                            <a href="/rfc/{{ $rfc->slug }}" class="block sm:flex-grow text-gray-800 hover:text-red-600">{{ $rfc->title }}</a>
                            <div class="text-xs text-gray-400 sm:whitespace-nowrap sm:text-right">
                                @if($poll)
                                    <span class="text-amber-700">{{ $poll->summary() }}</span>
                                    @if($rfcActivity) · @endif
                                @endif
                                @if($rfcActivity)
                                    {{ $rfcActivity->threadCount }} {{ Str::plural('thread', $rfcActivity->threadCount) }}
                                    @if($rfcActivity->lastActivity)
                                        · <time datetime="{{ $rfcActivity->lastActivity->toAtomString() }}">{{ $rfcActivity->lastActivity->diffForHumans() }}</time>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if($hiddenCount > 0)
                <button type="button" wire:click="expand(@js($section['title']))" wire:loading.attr="disabled"
                        class="mt-2 text-sm text-gray-500 hover:text-gray-800 cursor-pointer">
                    <span wire:loading.remove wire:target="expand(@js($section['title']))">Show {{ $hiddenCount }} more</span>
                    <span wire:loading wire:target="expand(@js($section['title']))">Loading…</span>
                </button>
            @endif
        </details>
    @endforeach
</div>
