@props(['points', 'color' => '#c24e2a'])

@php
    $width = 640;
    $height = 220;
    $padL = 48;
    $padR = 12;
    $padT = 16;
    $padB = 32;
    $count = count($points);
    $values = array_map(fn ($point) => (int) $point['value'], $points);
    $max = max($values ?: [0]);
    $max = max($max, 1);
    $innerW = $width - $padL - $padR;
    $innerH = $height - $padT - $padB;
    $slot = $innerW / max($count, 1);
    $barW = max($slot - 8, 4);
@endphp

<svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="Calories over the last 14 days">
    <line x1="{{ $padL }}" y1="{{ $height - $padB }}" x2="{{ $width - $padR }}" y2="{{ $height - $padB }}" stroke="#e2d9cc" />
    <text x="8" y="{{ $padT + 8 }}" class="axis">{{ $max }}</text>
    @foreach ($points as $i => $point)
        @php
            $barH = ($point['value'] / $max) * $innerH;
            $x = $padL + ($slot * $i) + (($slot - $barW) / 2);
            $y = $padT + $innerH - $barH;
        @endphp
        <rect x="{{ round($x, 1) }}" y="{{ round($y, 1) }}" width="{{ round($barW, 1) }}" height="{{ round(max($barH, 0), 1) }}" rx="3" fill="{{ $color }}">
            <title>{{ $point['label'] }}: {{ $point['value'] }} kcal</title>
        </rect>
    @endforeach
    @if ($count > 0)
        <text x="{{ $padL }}" y="{{ $height - 8 }}" class="axis">{{ $points[0]['label'] }}</text>
        <text x="{{ $width - $padR }}" y="{{ $height - 8 }}" text-anchor="end" class="axis">{{ $points[$count - 1]['label'] }}</text>
    @endif
</svg>
