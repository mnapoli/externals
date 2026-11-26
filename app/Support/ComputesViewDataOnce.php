<?php

declare(strict_types=1);

namespace App\Support;

/**
 * For Volt components: Livewire and Volt both call `with()` on each render, which ran every query twice.
 * The component puts its data in `viewData()` instead, computed once per request.
 */
trait ComputesViewDataOnce
{
    /** @var array<string, mixed>|null */
    private ?array $computedViewData = null;

    /**
     * @return array<string, mixed>
     */
    abstract protected function viewData(): array;

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return $this->computedViewData ??= $this->viewData();
    }
}
