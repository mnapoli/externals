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
            $this->js(<<<'JS'
                alert('You must log in to vote. To log in, click the "log in" link in the top-right corner.');
            JS);

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

<div class="vote-actions inline-flex items-center gap-1 text-sm text-gray-300 whitespace-nowrap" title="Community vote">
    @if($canVote)
        <a class="vote-action upvote text-xl font-bold leading-none {{ $userVote === 1 ? 'active' : '' }}"
           href="#" wire:click.prevent="vote(1)" title="Upvote">&blacktriangle;</a>
    @else
        <span class="text-xl leading-none" title="Upvotes">&blacktriangle;</span>
    @endif
    <span class="min-w-[2ch] tabular-nums @if($upvotes > 0) text-gray-600 @endif">{{ $upvotes }}</span>
    @if($canVote)
        <a class="vote-action downvote text-xl font-bold leading-none {{ $userVote === -1 ? 'active' : '' }}"
           href="#" wire:click.prevent="vote(-1)" title="Downvote">&blacktriangledown;</a>
    @else
        <span class="text-xl leading-none" title="Downvotes">&blacktriangledown;</span>
    @endif
    <span class="min-w-[2ch] tabular-nums @if($downvotes > 0) text-gray-600 @endif">{{ $downvotes }}</span>
</div>
