@props([
    'title',
    'subtitle' => null,
    'icon',
    'color' => 'violet',
])

@php
    $colors = [
        'emerald' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        'rose' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
        'cyan' => 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400',
        'violet' => 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
        'amber' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        'blue' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
        'zinc' => 'bg-zinc-500/10 text-zinc-600 dark:text-zinc-400',
    ];
    $chip = $colors[$color] ?? $colors['violet'];
@endphp

<div class="flex items-center gap-3">
    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $chip }}">
        <flux:icon :name="$icon" variant="outline" class="size-6" />
    </span>
    <div>
        <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
        @if ($subtitle)
            <flux:subheading>{{ $subtitle }}</flux:subheading>
        @elseif ($slot->isNotEmpty())
            {{ $slot }}
        @endif
    </div>
</div>
