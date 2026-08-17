@props([
    'values' => [],
    'labels' => [],
    'height' => 120,
])

@php
    $count = count($values);
    $max = $count ? max($values) : 0;
    $min = $count ? min($values) : 0;
    $range = max($max - $min, 1);
    $points = [];
    foreach (array_values($values) as $i => $v) {
        $x = $count > 1 ? ($i / ($count - 1)) * 100 : 50;
        $y = 38 - (($v - $min) / $range) * 34;
        $points[] = [$x, $y];
    }
    $linePath = '';
    foreach ($points as $i => [$x, $y]) {
        $linePath .= ($i === 0 ? "M {$x},{$y}" : " L {$x},{$y}");
    }
    $areaPath = $linePath;
    if (count($points) > 1) {
        $areaPath .= ' L '.$points[array_key_last($points)][0].',40 L '.$points[0][0].',40 Z';
    }
    $gradientId = 'lc-'.substr(md5(serialize($values).microtime()), 0, 8);
@endphp

<div class="w-full">
    <svg viewBox="0 0 100 40" preserveAspectRatio="none" style="height: {{ $height }}px" class="w-full overflow-visible">
        <defs>
            <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="currentColor" stop-opacity="0.35" />
                <stop offset="100%" stop-color="currentColor" stop-opacity="0" />
            </linearGradient>
        </defs>
        @if (count($points) > 1)
            <path d="{{ $areaPath }}" fill="url(#{{ $gradientId }})" stroke="none"></path>
            <path d="{{ $linePath }}" fill="none" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round"></path>
            @foreach ($points as [$x, $y])
                <circle cx="{{ $x }}" cy="{{ $y }}" r="1.6" fill="currentColor"></circle>
            @endforeach
        @elseif (count($points) === 1)
            <circle cx="{{ $points[0][0] }}" cy="{{ $points[0][1] }}" r="1.6" fill="currentColor"></circle>
        @endif
    </svg>
    @if (count($labels))
        <div class="mt-2 flex justify-between text-xs text-zinc-400">
            @foreach ($labels as $label)
                <span>{{ $label }}</span>
            @endforeach
        </div>
    @endif
</div>
