<x-layouts.app :title="$query !== '' ? $query . ' - Search - Externals' : 'Search - Externals'">
    @if($markdownUrl)
        @push('head')
            <link rel="alternate" type="text/markdown" href="{{ $markdownUrl }}" title="Search results as Markdown">
        @endpush
    @endif

    <div class="mx-auto max-w-4xl">
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mb-4">Search</h1>
        <form action="/search" method="get" role="search" class="mb-6">
            <label class="relative block">
                <span class="sr-only">Search</span>
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                <input type="search" name="q" value="{{ $query }}" placeholder="Search threads and emails" autofocus
                       class="w-full pl-10 pr-4 h-11 rounded-lg border border-line-strong bg-surface outline-none focus:border-accent-line focus:ring-4 focus:ring-accent-soft">
            </label>
        </form>

        @if($results)
            <p class="mb-3 font-mono text-xs text-muted">
                {{ number_format($results->totalHits) }} {{ Str::plural('result', $results->totalHits) }}
                @if($results->pageCount > 1) · page {{ $results->page }} of {{ $results->pageCount }}@endif
            </p>

            @if($results->hits !== [])
                <ol class="bg-surface border border-line rounded-lg overflow-hidden">
                    @foreach($results->hits as $hit)
                        @php($subject = \App\Support\Email\SubjectTags::parse($hit->subject))
                        @php($avatar = $avatars[$hit->number] ?? null)
                        <li class="flex gap-3 px-3 sm:px-4 py-3.5 border-b border-line last:border-b-0 hover:bg-[#fafaff]">
                            <span class="relative mt-0.5 w-7 h-7 shrink-0 rounded-full bg-[#e4e4ea] text-[10px] font-semibold text-ink-soft flex items-center justify-center select-none">
                                <span aria-hidden="true">{{ (new \App\Support\Email\Participant($hit->author, null, 1))->initials() }}</span>
                                @if($avatar)
                                    <img src="{{ $avatar }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full rounded-full object-cover">
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    @foreach($subject->tags as $tag)
                                        <span class="label px-1.5 py-0.5 rounded whitespace-nowrap bg-subtle text-ink-soft">{{ $tag }}</span>
                                    @endforeach
                                    <a class="font-medium text-[15px] leading-snug text-ink hover:text-accent [overflow-wrap:anywhere]" href="/message/{{ $hit->threadNumber }}#{{ $hit->number }}">{{ $subject->title }}</a>
                                </div>
                                <p class="mt-0.5 text-[13px] text-muted">
                                    {{ $hit->author }} · <time class="font-mono text-xs" datetime="{{ $hit->date->toAtomString() }}" title="{{ $hit->date->format('D, M j, Y \a\t H:i') }} UTC">{{ $hit->date->diffForHumans() }}</time>
                                </p>
                                <p class="mt-1.5 text-sm text-ink-soft line-clamp-3 [overflow-wrap:anywhere] [&_mark]:bg-[#fdf1d8] [&_mark]:text-ink [&_mark]:rounded-sm [&_mark]:px-0.5">{!! $hit->snippetAsHtml() !!}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="py-10 text-center text-ink-soft">No results for <span class="font-semibold text-ink">"{{ $query }}"</span>. Try fewer or different words.</p>
            @endif

            @if($previousUrl || $nextUrl)
                <nav aria-label="Pagination" class="flex justify-between gap-3 mt-6 text-sm">
                    <span>@if($previousUrl)<a href="{{ $previousUrl }}" rel="prev" class="inline-block px-3 py-2 rounded-md border border-line-strong bg-surface hover:border-accent-line">← Previous</a>@endif</span>
                    <span>@if($nextUrl)<a href="{{ $nextUrl }}" rel="next" class="inline-block px-3 py-2 rounded-md border border-line-strong bg-surface hover:border-accent-line">Next →</a>@endif</span>
                </nav>
            @endif
        @else
            <p class="text-sm text-muted">
                Searches the subjects and contents of all the emails of the mailing list, for example
                <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="/search?q=pipe+operator">pipe operator</a>,
                <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="/search?q=generics">generics</a> or
                <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="/search?q=readonly">readonly</a>.
            </p>
        @endif
    </div>
</x-layouts.app>
