@props(['participants', 'max' => 10, 'maxMobile' => 6, 'size' => 'w-[26px] h-[26px] text-[10px]'])

{{-- Stacked faces of the people in a thread; beyond the limit, the last spot says how many more --}}
@php($count = count($participants))
@php($shown = $count > $max ? $max - 1 : $count)
@php($shownMobile = $count > $maxMobile ? $maxMobile - 1 : $count)
<span {{ $attributes->merge(['class' => 'flex shrink-0 pl-1.5']) }}>
    @foreach(array_slice($participants, 0, $shown) as $i => $participant)
        <x-avatar :name="$participant->name" :url="$participant->avatarUrl" :size="$size"
                  :display="$i < $shownMobile ? 'inline-flex' : 'hidden sm:inline-flex'" class="-ml-1.5 ring-2 ring-surface"
                  title="{{ $participant->name }} · {{ $participant->messageCount }} {{ Str::plural('message', $participant->messageCount) }}" />
    @endforeach
    @if($count > $shownMobile)
        <span class="sm:hidden -ml-1.5 inline-flex items-center justify-center rounded-full ring-2 ring-surface bg-surface border border-line-strong font-mono font-semibold text-muted {{ $size }}">+{{ $count - $shownMobile }}</span>
    @endif
    @if($count > $shown)
        <span class="hidden sm:inline-flex -ml-1.5 items-center justify-center rounded-full ring-2 ring-surface bg-surface border border-line-strong font-mono font-semibold text-muted {{ $size }}">+{{ $count - $shown }}</span>
    @endif
    <span class="sr-only">{{ $count }} {{ Str::plural('participant', $count) }}</span>
</span>
