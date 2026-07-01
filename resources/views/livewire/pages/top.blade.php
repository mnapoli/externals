<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    //
}; ?>

<div>
    <h1 class="text-xl mb-8">Most interesting threads of the last month</h1>

    <livewire:thread-list mode="top" />
</div>
