<x-layouts.app>
    <div class="mx-auto max-w-xl py-16 text-center">
        <p class="font-mono text-sm text-muted">Login failed</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">GitHub didn't let you in</h1>
        <p class="mt-3 text-ink-soft">There was an error while authenticating with GitHub:</p>
        <p class="mt-3 inline-block max-w-full px-3 py-1.5 rounded-md bg-subtle font-mono text-sm text-ink [overflow-wrap:anywhere]">{{ $error }}</p>
        <p class="mt-6 flex justify-center gap-3">
            <a href="/login" class="inline-flex items-center h-10 px-4 rounded-md bg-accent text-white font-semibold hover:bg-accent-strong">Try again</a>
            <a href="/" class="inline-flex items-center h-10 px-4 rounded-md border border-line-strong bg-surface hover:border-accent-line">Latest threads</a>
        </p>
    </div>
</x-layouts.app>
