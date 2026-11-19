<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="top">
        <a class="brand" href="{{ route('dashboard') }}">{{ config('app.name') }}</a>
        <div class="session">
            <p>Calories and weight, day by day.</p>
            @auth
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text">Log out</button>
                </form>
            @else
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
