@props(['poll'])

{{-- When the vote closes and how it goes, against the 2/3 majority needed --}}
@php($closesAt = \App\Services\Rfc\RfcHighlights::closesAt($poll))
@php($yesShare = $poll ? \App\Services\Rfc\RfcHighlights::yesShare($poll) : null)
<div {{ $attributes }}>
    @if($closesAt)
        <span class="block font-mono text-xs text-muted" title="{{ $poll->closesAt }}">{{ $closesAt->isFuture() ? 'Closes in ' . $closesAt->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) : 'Closed ' . $closesAt->diffForHumans() }}</span>
    @endif
    @if($yesShare !== null)
        <span class="relative block mt-2 h-1.5 rounded-full bg-subtle" role="img" aria-label="{{ $poll->summary() }}, {{ round($yesShare * 100) }}% yes, 2/3 needed">
            <span class="absolute inset-y-0 left-0 rounded-full {{ $yesShare >= 2 / 3 ? 'bg-pass' : 'bg-fail' }}" style="width: {{ round($yesShare * 100, 1) }}%"></span>
            <span class="absolute -inset-y-0.5 left-2/3 w-0.5 bg-ink" aria-hidden="true"></span>
        </span>
        <span class="mt-1.5 flex flex-wrap justify-between gap-x-3 font-mono text-xs text-ink-soft">
            <span>{{ $poll->summary() }}</span>
            <span class="{{ $yesShare >= 2 / 3 ? 'text-pass' : 'text-fail' }}">{{ $yesShare >= 2 / 3 ? 'passing' : 'failing' }}</span>
        </span>
    @endif
</div>
