@props([
    'label',
    'value',
    'icon',
    'color' => 'violet',
    'valueClass' => '',
])

@php
    $colors = [
        'emerald' => ['chip' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400', 'bar' => 'from-emerald-500 to-teal-400'],
        'rose' => ['chip' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400', 'bar' => 'from-rose-500 to-orange-400'],
        'cyan' => ['chip' => 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400', 'bar' => 'from-cyan-500 to-blue-400'],
        'violet' => ['chip' => 'bg-violet-500/10 text-violet-600 dark:text-violet-400', 'bar' => 'from-violet-500 to-blue-500'],
        'amber' => ['chip' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400', 'bar' => 'from-amber-500 to-orange-400'],
        'blue' => ['chip' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400', 'bar' => 'from-blue-500 to-cyan-400'],
        'zinc' => ['chip' => 'bg-zinc-500/10 text-zinc-600 dark:text-zinc-400', 'bar' => 'from-zinc-400 to-zinc-300'],
    ];
    $c = $colors[$color] ?? $colors['violet'];
@endphp

<flux:card class="relative flex flex-col gap-2 overflow-hidden">
    <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r {{ $c['bar'] }}"></div>
    <div class="flex items-center gap-3">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $c['chip'] }}">
            <flux:icon :name="$icon" variant="micro" class="size-5" />
        </span>
        <flux:subheading>{{ $label }}</flux:subheading>
    </div>
    <flux:heading size="lg" class="{{ $valueClass }}">{{ $value }}</flux:heading>
    {{ $slot }}
</flux:card>
