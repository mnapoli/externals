<?php

use App\Models\RfcVote;
use App\Services\Rfc\RfcList;
use App\Services\Rfc\RfcThreads;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function rendering(View $view): void
    {
        $view->title('PHP RFCs - Externals');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'sections' => app(RfcList::class)->sections(),
            'activity' => app(RfcThreads::class)->activityByRfc(),
            'userVotes' => auth()->check() ? RfcVote::where('userId', auth()->id())->pluck('value', 'rfcId') : collect(),
        ];
    }
}; ?>

@push('head')
    <link rel="alternate" type="text/markdown" href="/rfc.md" title="RFCs as Markdown">
@endpush

<div x-data="{ q: '' }">
    <h1 class="text-xl sm:text-3xl font-light mb-2">
        PHP RFCs
        <a href="/rfc.md" type="text/markdown" class="ml-2 align-middle text-xs font-normal text-gray-400 hover:text-gray-600" title="This list as Markdown, for AI agents">View as Markdown</a>
    </h1>
    <p class="text-sm text-gray-500 mb-6">
        The RFCs of <a class="underline" href="https://wiki.php.net/rfc">wiki.php.net/rfc</a>, synchronized every hour, with their discussions on the mailing list.
    </p>

    <input type="search" x-model="q" placeholder="Filter by title…" aria-label="Filter RFCs by title"
           class="w-full mb-8 px-4 py-2 rounded border border-gray-300 focus:border-gray-400 bg-white">

    @foreach($sections as $section)
        <details class="mb-6 group/section" @if($section['open']) open @endif
                 x-bind:open="q !== '' || {{ $section['open'] ? 'true' : 'false' }}"
                 x-show="q === '' || [...$el.querySelectorAll('[data-title]')].some(e => e.dataset.title.includes(q.toLowerCase()))">
            <summary class="cursor-pointer select-none text-lg text-gray-700 mb-2">
                {{ $section['title'] }}
                <span class="text-sm text-gray-400">({{ count($section['rfcs']) }})</span>
            </summary>
            <ul>
                @foreach($section['rfcs'] as $rfc)
                    @php($rfcActivity = $activity->get($rfc->slug))
                    @php($poll = $rfc->status === \App\Services\Rfc\RfcStatus::Voting ? $rfc->polls->first() : null)
                    <li wire:key="rfc-{{ $rfc->slug }}"
                        data-title="{{ mb_strtolower($rfc->title . ' ' . $rfc->slug) }}"
                        x-show="q === '' || $el.dataset.title.includes(q.toLowerCase())"
                        class="py-2 border-b border-gray-100 flex items-baseline gap-3">
                        @if($rfc->status->acceptsCommunityVotes() || $rfc->upvotes || $rfc->downvotes)
                            <livewire:rfc-vote :rfc-id="$rfc->id" :can-vote="$rfc->status->acceptsCommunityVotes()"
                                               :upvotes="(int) $rfc->upvotes" :downvotes="(int) $rfc->downvotes"
                                               :user-vote="(int) $userVotes->get($rfc->id, 0)"
                                               :key="'vote-' . $rfc->id" />
                        @endif
                        @if($rfc->authorAvatarUrl())
                            <img src="{{ $rfc->authorAvatarUrl() }}" alt="" title="{{ $rfc->authors }}" loading="lazy"
                                 class="self-center w-6 h-6 shrink-0 rounded-full bg-gray-100">
                        @else
                            <div class="self-center w-6 h-6 shrink-0 rounded-full bg-gray-100"></div>
                        @endif
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
        </details>
    @endforeach
</div>
