@props(['status', 'prefix' => null, 'phpVersion' => null, 'href' => null])

{{-- e.g. "RFC · Voting" or "Implemented · PHP 8.5", built here rather than with @if: Livewire would add markers inside the text --}}
@php($text = implode(' · ', array_filter([$prefix, $status->badgeLabel(), $phpVersion !== null ? "PHP {$phpVersion}" : null])))
@if($href)
    <a href="{{ $href }}" {{ $attributes->class(['chip', $status->badgeClasses()]) }}>{{ $text }}</a>
@else
    <span {{ $attributes->class(['chip', $status->badgeClasses()]) }}>{{ $text }}</span>
@endif
