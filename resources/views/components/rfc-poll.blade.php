@props(['poll'])

{{-- When the vote closes and how it goes, against the 2/3 majority needed --}}
@php($closes = \App\Services\Rfc\RfcHighlights::closesLabel($poll))
@php($yesShare = $poll ? \App\Services\Rfc\RfcHighlights::yesShare($poll) : null)
<div {{ $attributes }}>
    @if($closes)
        <span class="block font-mono text-xs text-muted" title="{{ $poll->closesAt }}">{{ $closes }}</span>
    @endif
    @if($yesShare !== null)
        @php($passing = \App\Services\Rfc\RfcHighlights::isPassing($yesShare))
        <span class="relative block mt-2 h-1.5 rounded-full bg-subtle" role="img" aria-label="{{ $poll->summary() }}, {{ round($yesShare * 100) }}% yes, 2/3 needed">
            <span class="absolute inset-y-0 left-0 rounded-full {{ $passing ? 'bg-pass' : 'bg-fail' }}" style="width: {{ round($yesShare * 100, 1) }}%"></span>
            <span class="absolute -inset-y-0.5 left-2/3 w-0.5 bg-ink" aria-hidden="true"></span>
        </span>
        <span class="mt-1.5 flex flex-wrap justify-between gap-x-3 font-mono text-xs text-ink-soft">
            <span>{{ $poll->summary() }}</span>
            <span class="{{ $passing ? 'text-pass' : 'text-fail' }}">{{ $passing ? 'passing' : 'failing' }}</span>
        </span>
    @endif
</div>
