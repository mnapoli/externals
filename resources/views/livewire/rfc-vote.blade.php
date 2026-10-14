<?php

use App\Actions\CastRfcVote;
use App\Models\Rfc;
use App\Models\RfcVote;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/*
 * Community vote on an RFC. A component of its own so that voting doesn't re-render the whole list of RFCs.
 */
new class extends Component {
    #[Locked]
    public int $rfcId;

    #[Locked]
    public bool $canVote = true;

    public int $upvotes = 0;
    public int $downvotes = 0;
    public int $userVote = 0;

    public function vote(int $value): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return;
        }

        $rfc = Rfc::findOrFail($this->rfcId);
        if (! $this->canVote || ! $rfc->status->acceptsCommunityVotes() || ! in_array($value, [1, -1], true)) {
            return;
        }

        // Clicking the arrow that is already active removes the vote
        $newValue = $this->userVote === $value ? 0 : $value;
        app(CastRfcVote::class)->handle((int) auth()->id(), $rfc, $newValue);

        $this->userVote = $newValue;
        $this->upvotes = RfcVote::where('rfcId', $rfc->id)->where('value', 1)->count();
        $this->downvotes = RfcVote::where('rfcId', $rfc->id)->where('value', -1)->count();
    }
}; ?>

<div class="inline-flex items-center gap-0.5 font-mono text-xs text-muted whitespace-nowrap" title="Community opinion on externals.io, not the official vote">
    @if($canVote)
        <button type="button" wire:click="vote(1)" aria-pressed="{{ $userVote === 1 ? 'true' : 'false' }}" aria-label="Upvote"
                class="w-7 h-7 rounded cursor-pointer {{ $userVote === 1 ? 'bg-accent-soft text-accent-strong' : 'text-faint hover:bg-subtle hover:text-ink' }}">▲</button>
    @else
        <span class="w-5 text-center text-faint" aria-label="Upvotes">▲</span>
    @endif
    <span class="min-w-[2ch] tabular-nums {{ $upvotes > 0 ? 'text-ink-soft' : '' }}">{{ $upvotes }}</span>
    @if($canVote)
        <button type="button" wire:click="vote(-1)" aria-pressed="{{ $userVote === -1 ? 'true' : 'false' }}" aria-label="Downvote"
                class="w-7 h-7 rounded cursor-pointer {{ $userVote === -1 ? 'bg-accent-soft text-accent-strong' : 'text-faint hover:bg-subtle hover:text-ink' }}">▼</button>
    @else
        <span class="w-5 text-center text-faint" aria-label="Downvotes">▼</span>
    @endif
    <span class="min-w-[2ch] tabular-nums {{ $downvotes > 0 ? 'text-ink-soft' : '' }}">{{ $downvotes }}</span>
</div>
