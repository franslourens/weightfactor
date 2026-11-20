<x-layout>
    <form class="card login" method="post" action="{{ route('account.update') }}">
        @csrf
        @method('PUT')
        <h2>Account</h2>
        <p class="hint">Your name is shown on your public page. The link stays <a href="{{ $publicUrl }}">{{ $publicUrl }}</a>.</p>
        <label>
            Name
            <input type="text" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" required>
        </label>
        @error('name')
            <p class="error">{{ $message }}</p>
        @enderror
        <p class="hint">Email {{ $user->email }}</p>
        <label>
            New password
            <input type="password" name="password" autocomplete="new-password">
        </label>
        @error('password')
            <p class="error">{{ $message }}</p>
        @enderror
        <label>
            Confirm new password
            <input type="password" name="password_confirmation" autocomplete="new-password">
        </label>
        <label>
            Current password
            <input type="password" name="current_password" autocomplete="current-password">
        </label>
        <p class="hint">Enter your current password to change the password. We'll email you when it changes.</p>
        @error('current_password')
            <p class="error">{{ $message }}</p>
        @enderror
        <button type="submit">Save account</button>
    </form>
</x-layout>
