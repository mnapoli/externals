<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    //
}; ?>

<div class="mx-auto max-w-5xl">
    <h1 class="text-2xl font-bold tracking-tight mb-1">Top threads</h1>
    <p class="text-sm text-muted mb-4">The most upvoted threads of the last month.</p>

    <livewire:thread-list mode="top" />
</div>
