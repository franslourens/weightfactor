<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weight Factor</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
    <header class="top">
        <a class="brand" href="{{ route('dashboard') }}">Weight Factor</a>
        <div class="session">
            <p>Calories and weight, day by day.</p>
            @auth
                @if (auth()->user()->isOwner())
                    <a href="{{ route('admin') }}">Admin</a>
                @endif
                <a href="{{ route('account.edit') }}">Account</a>
                @unless (auth()->user()->isOwner())
                    <a href="{{ auth()->user()->slug ? route('profile.show', auth()->user()) : route('profile.mine') }}">Your profile</a>
                @endunless
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text">Log out</button>
                </form>
            @else
                <a href="{{ route('register') }}">Sign up</a>
                <a href="{{ route('login') }}">Log in</a>
            @endauth
        </div>
    </header>
    <main>
        @if (session('status'))
            <p class="status">{{ session('status') }}</p>
        @endif
        {{ $slot }}
    </main>
    @stack('scripts')
</body>
</html>
