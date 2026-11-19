<x-layout>
    <form class="card login" method="post" action="{{ route('login.store') }}">
        @csrf
        <h2>Log in</h2>
        <p class="hint">Weight, food, and the target stay visible. Only this login can change them.</p>
        <label>
            Email
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required>
        </label>
        @error('email')
            <p class="error">{{ $message }}</p>
        @enderror
        <label>
            Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        @error('password')
            <p class="error">{{ $message }}</p>
        @enderror
        <button type="submit">Log in</button>
    </form>
</x-layout>
