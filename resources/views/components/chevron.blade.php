@props(['disclosure' => true])

{{-- Points down when open. In a <details class="group/disclosure"> it turns by itself, otherwise the caller binds the rotation --}}
<svg {{ $attributes->class(['shrink-0 transition-transform', '-rotate-90 group-open/disclosure:rotate-0' => $disclosure]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
