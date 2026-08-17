@props([
    'segments' => [],
    'size' => 116,
])

@php
    $total = collect($segments)->sum('value');
    $offset = 0;
    $r = 15.9155;
@endphp

<svg
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 42 42"
    class="-rotate-90 shrink-0"
    role="img"
    aria-label="{{ __('Gráfico de distribución') }}"
>
    <circle cx="21" cy="21" r="{{ $r }}" fill="transparent" stroke-width="6" class="stroke-zinc-200 dark:stroke-white/10"></circle>
    @if ($total > 0)
        @foreach ($segments as $segment)
            @php $pct = ((float) $segment['value'] / $total) * 100; @endphp
            @if ($pct > 0)
                <circle
                    cx="21" cy="21" r="{{ $r }}"
                    fill="transparent"
                    stroke="{{ $segment['color'] }}"
                    stroke-width="6"
                    stroke-linecap="round"
                    stroke-dasharray="{{ max($pct - 1.5, 0) }} {{ 100 - max($pct - 1.5, 0) }}"
                    stroke-dashoffset="{{ -$offset }}"
                ></circle>
                @php $offset += $pct; @endphp
            @endif
        @endforeach
    @endif
</svg>
