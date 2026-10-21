@props(['name', 'url' => null, 'size' => 'w-7 h-7 text-[10px]', 'display' => 'inline-flex'])

{{-- Initials on grey, under the photo when there is one: Gravatar serves a transparent image to people without --}}
<span {{ $attributes->class([$display, 'relative shrink-0 items-center justify-center rounded-full bg-[#e4e4ea] font-semibold text-ink-soft select-none', $size]) }}>
    <span aria-hidden="true">{{ \App\Support\Initials::of($name) }}</span>
    @if($url)
        <img src="{{ $url }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full rounded-full object-cover">
    @endif
</span>
