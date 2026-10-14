<?php

use App\Actions\CastVote;
use App\Models\Vote;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/*
 * Vote on a thread, on the thread page. A component of its own so that voting doesn't re-render the whole thread.
 */
new class extends Component {
    #[Locked]
    public int $number;

    public int $votes = 0;
    public int $userVote = 0;

    public function vote(int $value): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return;
        }
        if (! in_array($value, [1, -1], true)) {
            return;
        }

        // Clicking the arrow that is already active removes the vote
        $this->userVote = $this->userVote === $value ? 0 : $value;
        $this->votes = app(CastVote::class)->handle((int) auth()->id(), $this->number, $this->userVote);
    }
}; ?>

<div class="flex flex-col items-center pt-1 font-mono text-sm text-ink-soft" role="group" aria-label="Thread vote">
    <button type="button" wire:click="vote(1)" aria-pressed="{{ $userVote === 1 ? 'true' : 'false' }}" aria-label="Upvote this thread"
            class="w-9 h-7 rounded text-xs cursor-pointer {{ $userVote === 1 ? 'bg-accent-soft text-accent-strong' : 'text-faint hover:bg-subtle hover:text-ink' }}">▲</button>
    <span class="{{ $userVote ? 'text-accent-strong font-semibold' : '' }}">{{ $votes }}</span>
    <button type="button" wire:click="vote(-1)" aria-pressed="{{ $userVote === -1 ? 'true' : 'false' }}" aria-label="Downvote this thread"
            class="w-9 h-7 rounded text-xs cursor-pointer {{ $userVote === -1 ? 'bg-accent-soft text-accent-strong' : 'text-faint hover:bg-subtle hover:text-ink' }}">▼</button>
</div>
