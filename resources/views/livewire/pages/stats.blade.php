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

<div>
    <h1 class="text-xl mb-8">Stats!</h1>

    <p class="mb-2">There are:</p>

    <ul>
        <li>{{ $userCount }} users</li>
        <li>{{ $threadCount }} threads</li>
        <li>{{ $emailCount }} emails</li>
    </ul>
</div>
