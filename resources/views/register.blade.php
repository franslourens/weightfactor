<x-layout>
    <form class="card login" method="post" action="{{ route('register.store') }}">
        @csrf
        <h2>Sign up</h2>
        <p class="hint">Your profile is separate. The home page stays the main dashboard.</p>
        <label>
            Name
            <input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required>
        </label>
        @error('name')
            <p class="error">{{ $message }}</p>
        @enderror
        <label>
            Email
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required>
        </label>
        @error('email')
            <p class="error">{{ $message }}</p>
        @enderror
        <label>
            Password
            <input type="password" name="password" autocomplete="new-password" required>
        </label>
        @error('password')
            <p class="error">{{ $message }}</p>
        @enderror
        <label>
            Confirm password
            <input type="password" name="password_confirmation" autocomplete="new-password" required>
        </label>
        <button type="submit">Create account</button>
        <p class="hint">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
    </form>
</x-layout>
