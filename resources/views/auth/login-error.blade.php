<x-layouts.app>
    <div class="mx-auto max-w-xl py-12">
        <h1 class="text-2xl font-bold tracking-tight mb-3">Login failed</h1>
        <p class="mb-4 text-ink-soft">It seems there was an error while authenticating with GitHub:</p>
        <blockquote class="border-l-2 border-line-strong pl-3 font-mono text-sm">{{ $error }}</blockquote>
        <p class="mt-6"><a href="/" class="text-accent-strong underline underline-offset-2">Back to the latest threads</a></p>
    </div>
</x-layouts.app>
