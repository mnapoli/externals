<?php

use App\Actions\CastVote;
use App\Models\Email;
use App\Models\Vote;
use App\Services\Email\ThreadQuery;
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
            $this->js(<<<'JS'
                alert('You must log in to vote. To log in, click the "log in" link in the top-right corner.');
            JS);

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
            return [
                'threads' => app(ThreadQuery::class)->findTopThreads(1, $user),
                'pageCount' => 1,
            ];
        }

        $threadCount = Cache::remember(
            'stats.threadCount',
            now()->addMinutes(5),
            fn() => Email::where('isThreadRoot', true)->count(),
        );

        return [
            'threads' => app(ThreadQuery::class)->findLatestThreads($this->page, $user),
            'pageCount' => (int) ceil($threadCount / 20),
        ];
    }
}; ?>

<div class="thread-list">
    @foreach($threads as $thread)
        <div wire:key="thread-{{ $thread->number }}"
             class="my-4 sm:my-8 flex items-center group @if($thread->votes < -1) opacity-25 @endif">
            <div class="mr-4 sm:mr-8 text-sm text-gray-300 text-center vote-actions">
                <a class="vote-action upvote text-2xl font-bold leading-none {{ $thread->userVote === 1 ? 'active' : '' }}"
                   href="#" wire:click.prevent="vote({{ $thread->number }}, 1)" title="Upvote">
                    &blacktriangle;
                </a>
                <div class="vote vote-value leading-none @if($thread->votes > 0) text-gray-500 @endif">{{ $thread->votes }}</div>
                <a class="vote-action downvote text-2xl font-bold leading-none {{ $thread->userVote === -1 ? 'active' : '' }}"
                   href="#" wire:click.prevent="vote({{ $thread->number }}, -1)" title="Downvote">
                    &blacktriangledown;
                </a>
            </div>
            <div class="flex-grow">
                <a class="block text-gray-800 sm:text-lg mb-1 @if(auth()->check() && ! $thread->isRead && $thread->userVote >= 0) font-bold @endif" href="/message/{{ $thread->number }}">
                    {{ $thread->subject }}
                </a>
                <div class="text-gray-400 text-xs sm:text-sm">
                    {{ $thread->date->diffForHumans() }}
                    @if($thread->fromName)
                        by {{ $thread->fromName }}
                    @endif
                </div>
            </div>
            @if($thread->emailCount > 1)
                <a href="/message/{{ $thread->number }}"
                   class="bg-gradient-to-r from-white to-gray-50 text-gray-300 group-hover:to-red-50 group-hover:text-red-400 transition-colors rounded-r-lg px-4 py-3 relative hidden sm:block">
                    <svg class="w-10 h-10 text-current"
                         xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><path d='M431 320.6c-1-3.6 1.2-8.6 3.3-12.2a33.68 33.68 0 012.1-3.1A162 162 0 00464 215c.3-92.2-77.5-167-173.7-167-83.9 0-153.9 57.1-170.3 132.9a160.7 160.7 0 00-3.7 34.2c0 92.3 74.8 169.1 171 169.1 15.3 0 35.9-4.6 47.2-7.7s22.5-7.2 25.4-8.3a26.44 26.44 0 019.3-1.7 26 26 0 0110.1 2l56.7 20.1a13.52 13.52 0 003.9 1 8 8 0 008-8 12.85 12.85 0 00-.5-2.7z' fill='none' stroke='currentColor' stroke-linecap='round' stroke-miterlimit='10' stroke-width='32'/><path d='M66.46 232a146.23 146.23 0 006.39 152.67c2.31 3.49 3.61 6.19 3.21 8s-11.93 61.87-11.93 61.87a8 8 0 002.71 7.68A8.17 8.17 0 0072 464a7.26 7.26 0 002.91-.6l56.21-22a15.7 15.7 0 0112 .2c18.94 7.38 39.88 12 60.83 12A159.21 159.21 0 00284 432.11' fill='none' stroke='currentColor' stroke-linecap='round' stroke-miterlimit='10' stroke-width='32'/></svg>
                    <span class="absolute inset-0 text-center mt-4 ml-1 text-sm">{{ $thread->emailCount }}</span>
                </a>
            @endif
        </div>
    @endforeach

    @if($mode === 'latest')
        <div class="my-12 border-t flex justify-center">
            @if($page > 1)
                <button type="button" wire:click="$set('page', 1)" class="block w-10 py-4 text-center text-gray-400 hover:text-gray-800 cursor-pointer" style="margin-top: 1px">
                    1
                </button>
            @endif
            @if($page > 5)
                <div class="block w-10 py-4 text-center text-gray-400" style="margin-top: 1px">…</div>
            @endif
            @if($page > 2)
                @for($i = max($page - 3, 2); $i <= $page - 1; $i++)
                    <button type="button" wire:click="$set('page', {{ $i }})" class="block w-10 py-4 text-center text-gray-400 hover:text-gray-800 cursor-pointer" style="margin-top: 1px">
                        {{ $i }}
                    </button>
                @endfor
            @endif
            <div class="w-10 py-4 text-center border-t-2 border-red-200 text-red-400" style="margin-top: -1px">
                {{ $page }}
            </div>
            @if($page < $pageCount)
                @for($i = $page + 1; $i <= min($page + 4, $pageCount); $i++)
                    <button type="button" wire:click="$set('page', {{ $i }})" class="block w-10 py-4 text-center text-gray-400 hover:text-gray-800 cursor-pointer" style="margin-top: 1px">
                        {{ $i }}
                    </button>
                @endfor
            @endif
            @if($page + 4 < $pageCount)
                <div class="block w-10 py-4 text-center text-gray-400" style="margin-top: 1px">…</div>
            @endif
        </div>
    @endif
</div>
