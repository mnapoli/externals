@php($defaultTitle = "#externals - Opening PHP's #internals to the outside")
{{-- Pages can override the canonical URL, e.g. RFCs whose original is on wiki.php.net --}}
@php($canonicalUrl = $canonical ?? rtrim(config('app.url'), '/') . '/' . ltrim(request()->path(), '/') . (request()->integer('page') > 1 ? '?page=' . request()->integer('page') : ''))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="alternate" type="application/rss+xml" href="/rss" title="Latest emails"/>
    <link rel="alternate" type="application/rss+xml" href="/rss-rfc" title="Latest RFC Threads"/>

    <title>{{ $title ?? $defaultTitle }}</title>
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta name="google-site-verification" content="hTdWskuVWUMoQAWfM3WcbBH16xhAxqzOyjfJbQmRor0" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap">
    <link rel="preconnect" href="https://www.google-analytics.com">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" href="/assets/images/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="/assets/images/favicon-16x16.png" sizes="16x16" />

    <meta name="og:title" content="{{ $title ?? $defaultTitle }}">
    <meta name="og:type" content="website">
    <meta name="og:url" content="{{ $ogUrl ?? $canonicalUrl }}">
    <meta name="og:image" content="/assets/images/apple-touch-icon.png">
    <meta name="og:description" content="#externals - Opening PHP's #internals to the outside">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:site" content="@phpexternals">

    @if(config('externals.no_index'))
        <meta name="robots" content="noindex">
    @endif

    <script async defer data-domain="externals.io" src="https://plausible.io/js/plausible.js"></script>
    @stack('head')
</head>
<body class="min-h-dvh flex flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-surface focus:px-3 focus:py-2">Skip to content</a>

    <header class="sticky top-0 z-30 bg-surface border-b border-line"
            x-data
            @keydown.window="if ($event.key === '/' && ! ['INPUT', 'TEXTAREA'].includes($event.target.tagName)) { $event.preventDefault(); $refs.search.focus() }">
        <div class="mx-auto max-w-7xl px-3 sm:px-6 h-14 flex items-center gap-2 sm:gap-6">
            <a href="/" class="font-mono font-semibold text-base shrink-0" aria-label="externals, home"><span class="text-accent">#</span>externals</a>
            @php($section = request()->is('rfc', 'rfc/*') ? 'rfcs' : (request()->is('top') ? 'top' : (request()->is('/') ? 'latest' : null)))
            <nav aria-label="Main" class="flex gap-0.5 sm:gap-1 text-sm sm:text-[15px]">
                @foreach(['latest' => ['/', 'Latest'], 'top' => ['/top', 'Top'], 'rfcs' => ['/rfc', 'RFCs']] as $key => [$href, $label])
                    <a href="{{ $href }}" @if($section === $key) aria-current="page" @endif
                       class="px-2 sm:px-2.5 py-1.5 rounded-md {{ $section === $key ? 'bg-subtle text-ink font-semibold' : 'text-ink-soft hover:text-ink hover:bg-subtle' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <form action="/search" method="get" role="search" class="hidden md:flex flex-1 max-w-sm items-center gap-2 h-9 px-2.5 rounded-md border border-line-strong bg-ground focus-within:border-accent-line focus-within:bg-surface">
                <svg class="w-3.5 h-3.5 shrink-0 text-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input x-ref="search" type="search" name="q" value="{{ request()->is('search') ? request()->query('q') : '' }}" placeholder="Search threads"
                       aria-label="Search" class="flex-1 min-w-0 bg-transparent text-sm outline-none placeholder:text-faint">
                <span class="kbd" aria-hidden="true">/</span>
            </form>
            <div class="ml-auto flex items-center gap-1 sm:gap-2 text-sm">
                <a href="/search" class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-md text-ink hover:bg-subtle" aria-label="Search">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                </a>
                @auth
                    <img class="hidden sm:block w-6 h-6 rounded-full bg-subtle" src="https://github.com/{{ auth()->user()->name }}.png?size=48" alt="">
                    <span class="hidden sm:inline font-mono text-xs text-ink-soft">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-md text-muted hover:text-ink hover:bg-subtle cursor-pointer" aria-label="Log out" title="Log out">
                            <svg class="w-4 h-4" viewBox="0 0 512 512" aria-hidden="true"><path d="M304 336v40a40 40 0 01-40 40H104a40 40 0 01-40-40V136a40 40 0 0140-40h152c22.09 0 48 17.91 48 40v40M368 336l80-80-80-80M176 256h256" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" rel="nofollow" title="Log in to track unread emails, vote and react"
                       class="inline-flex items-center gap-2 h-9 px-2.5 sm:px-3 rounded-md border border-line-strong bg-surface text-ink hover:border-accent-line whitespace-nowrap">
                        <svg class="hidden sm:block w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.5 2 2 6.6 2 12.2c0 4.5 2.9 8.3 6.8 9.7.5.1.7-.2.7-.5v-1.8c-2.8.6-3.4-1.2-3.4-1.2-.5-1.2-1.1-1.5-1.1-1.5-.9-.6.1-.6.1-.6 1 .1 1.5 1 1.5 1 .9 1.6 2.4 1.1 2.9.8.1-.7.4-1.1.6-1.3-2.2-.3-4.6-1.1-4.6-5 0-1.1.4-2 1-2.7-.1-.3-.4-1.3.1-2.7 0 0 .8-.3 2.8 1a9.4 9.4 0 0 1 5 0c1.9-1.3 2.8-1 2.8-1 .5 1.4.2 2.4.1 2.7.6.7 1 1.6 1 2.7 0 3.9-2.4 4.7-4.6 5 .4.3.7.9.7 1.9v2.8c0 .3.2.6.7.5A10.1 10.1 0 0 0 22 12.2C22 6.6 17.5 2 12 2Z"/></svg>
                        <span>Log in<span class="hidden lg:inline"> with GitHub</span></span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main id="main" class="flex-1 w-full mx-auto max-w-7xl px-4 sm:px-6 py-6 sm:py-8">
        @if(session()->has('status'))
            <p role="status" class="mb-6 px-4 py-3 rounded-lg border border-accent-line bg-accent-soft text-sm text-ink-soft">{{ session('status') }}</p>
        @endif
        {{ $slot }}
    </main>

    <footer class="border-t border-line bg-surface text-sm text-muted">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 py-8 space-y-3">
            <p class="flex flex-wrap gap-x-5 gap-y-2">
                <span class="text-ink">Opening PHP's <a class="underline decoration-line-strong underline-offset-2 hover:text-accent" href="https://news-web.php.net/php.internals">#internals</a> mailing list to the outside.</span>
                <a class="hover:text-ink" href="https://github.com/mnapoli/externals">GitHub</a>
                <a class="hover:text-ink" href="/rss">RSS</a>
                <a class="hover:text-ink" href="/rss-rfc">RSS (RFCs)</a>
            </p>
            <p>
                Created by <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="https://mnapoli.fr">@mnapoli</a>, serverless with <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="https://bref.sh/" title="Serverless PHP applications">Bref</a>.
                Search by <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="https://www.algolia.com">Algolia</a>, avatars by GitHub and <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="https://gravatar.com">Gravatar</a>.
            </p>
            <p>
                <strong class="font-semibold text-ink-soft">About data:</strong> if you log in with GitHub, we store your GitHub ID, username, the verified email addresses of your GitHub account, and which threads you have already read. No GitHub token is stored.
                Your addresses are used to show your GitHub avatar next to the emails you send to the mailing list, and are never shared or sold.
            </p>
            <p>
                <strong class="font-semibold text-ink-soft">For AI agents:</strong> add <code class="font-mono text-xs">.md</code> to any thread URL to read it as Markdown (or send <code class="font-mono text-xs">Accept: text/markdown</code>),
                search with <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="/search.md?q=enum">/search.md?q=…</a>, list RFCs with <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="/rfc.md">/rfc.md</a>, or use the read-only <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="/api">JSON API</a>.
                Details in <a class="underline decoration-line-strong underline-offset-2 hover:text-ink" href="/llms.txt">/llms.txt</a>.
            </p>
        </div>
    </footer>
</body>
</html>
