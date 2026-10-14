@props(['item', 'reactions' => []])

{{-- Replies are indented up to this depth; deeper replies stay at that level and say who they answer --}}
@php($maxIndent = 3)
@php($email = $item->email)
@php($depth = $item->depth())
@php($parent = $item->parent?->email)
@php($tracksRead = auth()->check())
@php($unread = $tracksRead && ! $email->isRead)
@php($replyCount = $item->descendantCount())
@php($unreadReplies = $tracksRead ? $item->unreadDescendantCount() : 0)

<article id="{{ $email->number }}"
         x-data="{ open: true }"
         @thread-collapse-read.window="open = {{ $tracksRead && $email->isRead && $unreadReplies === 0 ? 'false' : 'open' }}"
         @thread-expand-all.window="open = true"
         @thread-reveal.window="if ($el.contains(document.getElementById($event.detail))) open = true"
         data-message
         @class(['group/msg', 'unread' => $unread])>
    <div @class([
            'rounded-lg border bg-surface group-target/msg:ring-4 group-target/msg:ring-accent-soft group-data-target/msg:ring-4 group-data-target/msg:ring-accent-soft',
            'border-accent-line' => $unread,
            'border-line' => ! $unread,
        ])>
        <header class="flex flex-wrap items-center gap-x-2.5 gap-y-1 pl-3 sm:pl-4 pr-1 py-1.5 text-[13px] text-muted" :class="open && 'border-b border-[#f0f0f4]'">
            @php($initials = collect(preg_split('/[\s.@_-]+/', $email->from->getNameOrEmail()))->filter()->take(2)->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode(''))
            <span class="relative w-7 h-7 shrink-0 rounded-full bg-subtle text-[10px] font-semibold text-muted flex items-center justify-center" aria-hidden="true">
                {{ $initials }}
                @if($email->from->email)
                    <img class="absolute inset-0 w-7 h-7 rounded-full" src="https://unavatar.io/{{ $email->from->email }}" alt="" loading="lazy">
                @endif
            </span>
            <span class="author font-semibold text-sm text-ink">{{ $email->from->getNameOrEmail() }}</span>
            @if($parent && $depth > $maxIndent)
                <a href="#{{ $parent->number }}" class="font-mono text-[11px] text-muted bg-subtle hover:text-ink px-1.5 py-0.5 rounded whitespace-nowrap"
                   title="In reply to {{ $parent->from->getNameOrEmail() }}">↳ {{ $parent->from->getNameOrEmail() }}</a>
            @endif
            <a class="font-mono text-xs text-muted hover:text-ink" href="#{{ $email->number }}" title="{{ $email->date->format('D, M j, Y \a\t H:i') }} UTC"><time datetime="{{ $email->date->format(DATE_ATOM) }}">{{ \Carbon\Carbon::instance($email->date)->diffForHumans() }}</time></a>
            @if($unread)
                <span class="w-[7px] h-[7px] rounded-full bg-accent" aria-hidden="true"></span><span class="sr-only">Unread</span>
            @endif
            <span x-show="! open" x-cloak class="font-mono text-xs">
                @if($replyCount > 0){{ $replyCount }} {{ Str::plural('reply', $replyCount) }}@endif
                @if($unreadReplies > 0) · <span class="text-accent-strong font-semibold">{{ $unreadReplies }} unread</span>@endif
            </span>
            <span class="ml-auto flex items-center">
                <a href="/email/{{ $email->number }}/source" target="_blank" rel="nofollow" class="hidden sm:inline px-2 py-1 rounded text-xs text-muted hover:bg-subtle hover:text-ink">source</a>
                <a href="{{ \App\Support\Email\EmailReplyUrl::build($email) }}" rel="nofollow" class="hidden sm:inline px-2 py-1 rounded text-xs text-muted hover:bg-subtle hover:text-ink">reply by email</a>
                <button type="button" @click="open = ! open" :aria-expanded="open" aria-label="Collapse or expand this message and its replies"
                        class="inline-flex items-center justify-center w-9 h-8 rounded text-muted hover:bg-subtle hover:text-ink cursor-pointer">
                    <svg class="w-4 h-4 transition-transform" :class="! open && '-rotate-90'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            </span>
        </header>
        <div x-show="open">
            <div class="email-content px-3 sm:px-[18px] pt-3 pb-1">
                {!! \App\Support\Email\EmailHtml::present($email->content) !!}
            </div>
            <div class="px-3 sm:px-[18px] pt-2 pb-3 flex flex-wrap items-center gap-2">
                <livewire:email-reactions :email-number="$email->number"
                                          :counts="$reactions[$email->number]['counts'] ?? []"
                                          :mine="$reactions[$email->number]['mine'] ?? []"
                                          :key="'reactions-' . $email->number" />
                <span class="sm:hidden ml-auto flex">
                    <a href="/email/{{ $email->number }}/source" target="_blank" rel="nofollow" class="px-2 py-1.5 text-xs text-muted">source</a>
                    <a href="{{ \App\Support\Email\EmailReplyUrl::build($email) }}" rel="nofollow" class="px-2 py-1.5 text-xs text-muted">reply</a>
                </span>
            </div>
        </div>
    </div>

    @if($item->replies !== [])
        <div x-show="open" @class([
                'mt-3 flex flex-col gap-3',
                'ml-2 sm:ml-3 pl-3 sm:pl-5 border-l border-line-strong/70' => $depth < $maxIndent,
            ])>
            @foreach($item->replies as $reply)
                <x-thread-item :item="$reply" :reactions="$reactions" />
            @endforeach
        </div>
    @endif
</article>
