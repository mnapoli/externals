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

    /** Big buttons that say what they do, for the page of the RFC */
    #[Locked]
    public bool $large = false;

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

<div @if(! $large) class="inline-flex items-center gap-0.5 font-mono text-xs text-muted whitespace-nowrap" title="Community opinion on externals.io, not the official vote" @endif>
    @if($large)
        @if($canVote)
            <div class="grid grid-cols-2 gap-2" role="group" aria-label="Community vote">
                @foreach([1 => ['▲', 'In favor', $upvotes], -1 => ['▼', 'Against', $downvotes]] as $value => [$arrow, $label, $count])
                    <button type="button" wire:click="vote({{ $value }})" aria-pressed="{{ $userVote === $value ? 'true' : 'false' }}"
                            @class([
                                'inline-flex items-center justify-center gap-2 h-10 px-3 rounded-md border text-sm font-medium cursor-pointer transition-colors',
                                'border-accent bg-accent text-white' => $userVote === $value,
                                'border-line-strong bg-surface text-ink hover:border-accent-line hover:bg-accent-soft' => $userVote !== $value,
                            ])>
                        <span class="text-xs" aria-hidden="true">{{ $arrow }}</span>
                        {{ $label }}
                        <span @class(['font-mono tabular-nums', 'text-white/80' => $userVote === $value, 'text-muted' => $userVote !== $value])>{{ $count }}</span>
                    </button>
                @endforeach
            </div>
            @guest
                <p class="mt-2 text-xs text-muted"><a href="{{ route('login') }}" class="text-accent-strong underline decoration-accent-line underline-offset-2 hover:decoration-accent-strong">Log in with GitHub</a> to vote.</p>
            @endguest
        @else
            <p class="flex gap-4 font-mono text-sm text-ink-soft">
                <span><span class="text-faint" aria-hidden="true">▲</span> {{ $upvotes }} in favor</span>
                <span><span class="text-faint" aria-hidden="true">▼</span> {{ $downvotes }} against</span>
            </p>
        @endif
    @else
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
    @endif
</div>
