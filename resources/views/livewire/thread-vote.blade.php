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

<div class="pt-1">
    <x-thread-score :votes="$votes" :user-vote="$userVote" up="vote(1)" down="vote(-1)" large />
</div>
