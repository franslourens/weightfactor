@props(['points', 'color' => '#0e6b4f', 'unit' => 'kg'])

@php
    $width = 640;
    $height = 220;
    $padL = 56;
    $padR = 16;
    $padT = 16;
    $padB = 32;
    $count = count($points);
@endphp

@if ($count === 0)
    <p class="empty">Log a weigh-in to start the trend.</p>
@else
    @php
        $values = array_map(fn ($point) => (float) $point['value'], $points);
        $min = min($values);
        $max = max($values);
        if ($min === $max) {
            $min -= 1;
            $max += 1;
        }
        $span = $max - $min;
        $innerW = $width - $padL - $padR;
        $innerH = $height - $padT - $padB;
        $coords = [];
        foreach ($points as $i => $point) {
            $x = $count === 1 ? $padL + ($innerW / 2) : $padL + ($innerW * $i / ($count - 1));
            $y = $padT + $innerH - (((float) $point['value'] - $min) / $span) * $innerH;
            $coords[] = ['x' => $x, 'y' => $y, 'label' => $point['label'], 'value' => $point['value']];
        }
        $poly = collect($coords)->map(fn ($coord) => round($coord['x'], 1).','.round($coord['y'], 1))->implode(' ');
    @endphp
    <svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="Weight over time">
        <line x1="{{ $padL }}" y1="{{ $padT }}" x2="{{ $padL }}" y2="{{ $height - $padB }}" stroke="#e2d9cc" />
        <line x1="{{ $padL }}" y1="{{ $height - $padB }}" x2="{{ $width - $padR }}" y2="{{ $height - $padB }}" stroke="#e2d9cc" />
        <text x="8" y="{{ $padT + 8 }}" class="axis">{{ number_format($max, 2) }}</text>
        <text x="8" y="{{ $height - $padB }}" class="axis">{{ number_format($min, 2) }}</text>
        <polyline points="{{ $poly }}" fill="none" stroke="{{ $color }}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
        @foreach ($coords as $coord)
            <circle cx="{{ round($coord['x'], 1) }}" cy="{{ round($coord['y'], 1) }}" r="4" fill="{{ $color }}">
                <title>{{ $coord['label'] }}: {{ number_format($coord['value'], 2) }} {{ $unit }}</title>
            </circle>
        @endforeach
        <text x="{{ round($coords[0]['x'], 1) }}" y="{{ $height - 8 }}" class="axis">{{ $coords[0]['label'] }}</text>
        @if ($count > 1)
            <text x="{{ round($coords[$count - 1]['x'], 1) }}" y="{{ $height - 8 }}" text-anchor="end" class="axis">{{ $coords[$count - 1]['label'] }}</text>
        @endif
    </svg>
@endif
