<x-layouts.app :title="$query !== '' ? $query . ' - Search - Externals' : 'Search - Externals'">
    @if($markdownUrl)
        @push('head')
            <link rel="alternate" type="text/markdown" href="{{ $markdownUrl }}" title="Search results as Markdown">
        @endpush
    @endif

    <form action="/search" method="get" class="mb-6">
        <input type="search" name="q" value="{{ $query }}" placeholder="Search..." autofocus
               class="w-full px-4 py-2 transition border-b border-transparent border-gray-200 focus:border-red-400">
    </form>

    @if($results)
        <p class="text-gray-500 text-sm mb-4">{{ $results->totalHits }} {{ Str::plural('result', $results->totalHits) }}</p>

        @forelse($results->hits as $hit)
            <article class="mb-6">
                <h2 class="text-lg"><a class="underline" href="/message/{{ $hit->number }}">{{ $hit->subject }}</a></h2>
                <p class="text-gray-400 text-sm">
                    by <strong>{{ $hit->author }}</strong>
                    on <time datetime="{{ $hit->date->toAtomString() }}">{{ $hit->date->toDateString() }}</time>
                </p>
                <p class="text-sm text-gray-600 [&_mark]:bg-yellow-100">{!! $hit->snippetAsHtml() !!}</p>
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
</x-layouts.app>
