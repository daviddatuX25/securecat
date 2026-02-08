<?php

use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
};
?>

<div class="mt-4 p-4 rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
    <p class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">TALL stack check: Livewire + Alpine + Tailwind</p>
    <button
        wire:click="increment"
        class="px-4 py-2 text-sm rounded-sm bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] hover:bg-black dark:hover:bg-white transition-colors"
    >
        Clicks: {{ $count }}
    </button>
</div>