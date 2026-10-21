@props(['votes', 'userVote', 'up', 'down', 'large' => false])

{{-- ▲ score ▼ of a thread: $up and $down are the Livewire actions of the arrows --}}
@php($arrow = $large ? 'w-9 h-7 text-xs' : 'w-9 h-6 sm:h-5 text-[11px]')
<div {{ $attributes->class(['flex flex-col items-center font-mono text-ink-soft', $large ? 'text-sm' : 'text-[13px]']) }} role="group" aria-label="Thread vote">
    <button type="button" wire:click="{{ $up }}" aria-pressed="{{ $userVote === 1 ? 'true' : 'false' }}" aria-label="Upvote"
            @class(['rounded cursor-pointer', $arrow, 'bg-accent-soft text-accent-strong' => $userVote === 1, 'text-faint hover:bg-subtle hover:text-ink' => $userVote !== 1])>▲</button>
    <span @class(['leading-tight', 'text-accent-strong font-semibold' => $userVote])>{{ $votes }}</span>
    <button type="button" wire:click="{{ $down }}" aria-pressed="{{ $userVote === -1 ? 'true' : 'false' }}" aria-label="Downvote"
            @class(['rounded cursor-pointer', $arrow, 'bg-accent-soft text-accent-strong' => $userVote === -1, 'text-faint hover:bg-subtle hover:text-ink' => $userVote !== -1])>▼</button>
</div>
