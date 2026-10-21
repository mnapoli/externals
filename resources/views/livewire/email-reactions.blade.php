<?php

use App\Actions\ToggleReaction;
use App\Services\Email\ReactionQuery;
use App\Support\Reaction;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/*
 * Emoji reactions on one email. A component of its own so that reacting doesn't re-render the whole thread.
 */
new class extends Component {
    #[Locked]
    public int $emailNumber;

    /** @var list<array{reaction: string, count: int}> */
    public array $counts = [];

    /** @var list<string> Reactions of the current user */
    public array $mine = [];

    public function toggle(string $reaction): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return;
        }
        $reaction = Reaction::tryFrom($reaction);
        if ($reaction === null) {
            return;
        }

        app(ToggleReaction::class)->handle((int) auth()->id(), $this->emailNumber, $reaction);

        $reactions = app(ReactionQuery::class)->forEmails([$this->emailNumber], (int) auth()->id());
        $this->counts = $reactions[$this->emailNumber]['counts'] ?? [];
        $this->mine = $reactions[$this->emailNumber]['mine'] ?? [];
    }
}; ?>

<div class="flex flex-wrap items-center gap-1.5" x-data="{ picking: false }" @keydown.escape="picking = false" @click.outside="picking = false">
    @foreach($counts as ['reaction' => $key, 'count' => $count])
        @php($reaction = Reaction::from($key))
        @php($pressed = in_array($key, $mine, true))
        <button type="button" wire:click="toggle('{{ $key }}')" aria-pressed="{{ $pressed ? 'true' : 'false' }}"
                aria-label="{{ $reaction->label() }}: {{ $count }}{{ $pressed ? ', including you' : '' }}" title="{{ $reaction->label() }}"
                class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-full border font-mono text-[13px] cursor-pointer {{ $pressed ? 'bg-accent-soft border-accent-line text-accent-strong' : 'bg-surface border-line text-ink-soft hover:border-line-strong' }}">
            <span aria-hidden="true" class="font-sans">{{ $reaction->emoji() }}</span><span>{{ $count }}</span>
        </button>
    @endforeach
    <div class="relative">
        @auth
            <button type="button" @click="picking = ! picking" :aria-expanded="picking" aria-label="Add a reaction" title="Add a reaction"
                    class="inline-flex items-center justify-center w-8 h-7 rounded-full border border-dashed border-line-strong text-faint hover:text-accent-strong hover:border-accent-line cursor-pointer"
                    :class="picking && 'border-solid border-accent-line text-accent-strong'">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="12" r="8"/><path d="M8 14s1 1.5 3 1.5 3-1.5 3-1.5M8.5 9.5h.01M13.5 9.5h.01M20 3v4M18 5h4"/></svg>
            </button>
            <div x-show="picking" x-cloak x-transition.opacity.duration.100ms role="menu" aria-label="Pick a reaction"
                 class="absolute left-0 bottom-full mb-1.5 z-20 flex gap-0.5 p-1 bg-surface border border-line-strong rounded-xl shadow-lg">
                @foreach(Reaction::cases() as $reaction)
                    <button type="button" role="menuitem" wire:click="toggle('{{ $reaction->value }}')" @click="picking = false"
                            aria-label="{{ $reaction->label() }}" title="{{ $reaction->label() }}"
                            class="w-9 h-9 rounded-lg text-lg cursor-pointer hover:bg-accent-soft {{ in_array($reaction->value, $mine, true) ? 'bg-accent-soft' : '' }}">{{ $reaction->emoji() }}</button>
                @endforeach
            </div>
        @else
            <a href="{{ route('login') }}" rel="nofollow" aria-label="Log in to react" title="Log in with GitHub to react"
               class="inline-flex items-center justify-center w-8 h-7 rounded-full border border-dashed border-line-strong text-faint hover:text-accent-strong hover:border-accent-line">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="12" r="8"/><path d="M8 14s1 1.5 3 1.5 3-1.5 3-1.5M8.5 9.5h.01M13.5 9.5h.01M20 3v4M18 5h4"/></svg>
            </a>
        @endauth
    </div>
</div>
