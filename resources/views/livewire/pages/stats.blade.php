<?php

use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $ttl = now()->addMinutes(5);

        return [
            'userCount' => Cache::remember('stats.userCount', $ttl, fn() => User::count()),
            'threadCount' => Cache::remember('stats.threadCount', $ttl, fn() => Email::where('isThreadRoot', true)->count()),
            'emailCount' => Cache::remember('stats.emailCount', $ttl, fn() => Email::count()),
        ];
    }
}; ?>

<div class="mx-auto max-w-4xl">
    <h1 class="text-2xl font-bold tracking-tight mb-6">Stats</h1>

    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach(['users' => $userCount, 'threads' => $threadCount, 'emails' => $emailCount] as $label => $count)
            <div class="bg-surface border border-line rounded-lg px-5 py-4">
                <dt class="label text-muted">{{ Str::plural(Str::singular($label), $count) }}</dt>
                <dd class="mt-1 text-3xl font-bold tabular-nums">{{ number_format($count) }}</dd>
            </div>
        @endforeach
    </dl>
</div>
