<x-layouts.app :title="$query !== '' ? $query . ' - Search - Externals' : 'Search - Externals'">
    @if($markdownUrl)
        @push('head')
            <link rel="alternate" type="text/markdown" href="{{ $markdownUrl }}" title="Search results as Markdown">
        @endpush
    @endif

    <div class="mx-auto max-w-4xl">
    <h1 class="text-2xl font-bold tracking-tight mb-4">Search</h1>
    <form action="/search" method="get" role="search" class="mb-6">
        <input type="search" name="q" value="{{ $query }}" placeholder="Search threads and emails" aria-label="Search" autofocus
               class="w-full px-4 h-11 rounded-md border border-line-strong bg-surface outline-none focus:border-accent-line">
    </form>

    @if($results)
        <p class="text-muted text-sm mb-4">{{ $results->totalHits }} {{ Str::plural('result', $results->totalHits) }}</p>

        @forelse($results->hits as $hit)
            <article class="mb-6">
                <h2 class="text-lg font-semibold"><a class="hover:text-accent" href="/message/{{ $hit->threadNumber }}#{{ $hit->number }}">{{ $hit->subject }}</a></h2>
                <p class="text-muted text-sm">
                    by <strong>{{ $hit->author }}</strong>
                    on <time datetime="{{ $hit->date->toAtomString() }}">{{ $hit->date->toDateString() }}</time>
                </p>
                <p class="mt-1 text-sm text-ink-soft [&_mark]:bg-[#fdf1d8] [&_mark]:text-ink">{!! $hit->snippetAsHtml() !!}</p>
            </article>
        @empty
            <p>No results found for the search <em>"{{ $query }}"</em>.</p>
        @endforelse

        @if($previousUrl || $nextUrl)
            <nav class="flex justify-between my-8">
                <span>@if($previousUrl)<a class="underline" href="{{ $previousUrl }}" rel="prev">← Previous</a>@endif</span>
                <span>@if($nextUrl)<a class="underline" href="{{ $nextUrl }}" rel="next">Next →</a>@endif</span>
            </nav>
        @endif
    @endif
    </div>
</x-layouts.app>
