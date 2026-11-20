<section class="card steps">
    <h2>Steps</h2>
    <p class="figure">{{ number_format($stepTotal) }} <span>steps</span></p>
    <div class="step-nav">
        @if ($stepPreviousUrl)
            <a href="{{ $stepPreviousUrl }}">{{ $stepPreviousLabel }}</a>
        @else
            <span></span>
        @endif
        <p class="meta">{{ $stepMonth }}</p>
        @if ($stepNextUrl)
            <a href="{{ $stepNextUrl }}">{{ $stepNextLabel }}</a>
        @else
            <span></span>
        @endif
    </div>
    @error('steps')
        <p class="error">{{ $message }}</p>
    @enderror
    @foreach ($errors->get('steps.*') as $messages)
        @foreach ($messages as $message)
            <p class="error">{{ $message }}</p>
        @endforeach
    @endforeach
    @if ($canEditSteps)
        <form method="post" action="{{ route('steps.store') }}">
            @csrf
    @endif
    <div class="step-month">
        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
            <span class="step-head">{{ $weekday }}</span>
        @endforeach
        @foreach ($stepCells as $cell)
            @if ($cell === null)
                <span class="step-pad"></span>
            @else
                <div @class(['step-day', 'today' => $cell['today']])>
                    <span>{{ $cell['label'] }}</span>
                    @if ($canEditSteps)
                        <input type="number" name="steps[{{ $cell['date'] }}]" min="0" max="200000" step="1" inputmode="numeric" value="{{ old('steps.'.$cell['date'], $cell['steps']) }}" aria-label="Steps for {{ $cell['label'] }}">
                    @else
                        <strong>{{ $cell['steps'] === null ? '—' : number_format($cell['steps']) }}</strong>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
    @if ($canEditSteps)
            <button type="submit">Save steps</button>
        </form>
    @endif
</section>
