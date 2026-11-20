<form class="card" method="post" action="{{ route('weights.store') }}">
    @csrf
    <h2>Log weight</h2>
    @if (! $latest)
        <p class="hint">Log your weight before you can update the target.</p>
    @endif
    <label>
        Date
        <input type="date" name="recorded_on" value="{{ old('recorded_on', $today) }}" required>
    </label>
    @error('recorded_on')
        <p class="error">{{ $message }}</p>
    @enderror
    <label>
        Weight (kg)
        <input type="number" name="weight_kg" min="20" max="400" step="0.01" value="{{ old('weight_kg') }}" required>
    </label>
    @error('weight_kg')
        <p class="error">{{ $message }}</p>
    @enderror
    <button type="submit">Save weight</button>
    <p class="hint">One weight per day. Saving the same date replaces it.</p>
</form>
