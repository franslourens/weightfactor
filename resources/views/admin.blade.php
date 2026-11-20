<x-layout>
    <section class="card">
        <h2>Users</h2>
        <p class="hint">{{ $online }} logged in.</p>
        <div class="users-wrap">
            <table class="users">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Signed up</th>
                        <th>Last active</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                @if ($row['user']->slug)
                                    <a href="{{ route('profile.show', $row['user']) }}">{{ $row['user']->name }}</a>
                                @else
                                    {{ $row['user']->name }}
                                @endif
                            </td>
                            <td>{{ $row['user']->email }}</td>
                            <td>{{ $row['user']->slug }}</td>
                            <td>{{ $row['user']->created_at->timezone(config('app.timezone'))->format('M j, Y') }}</td>
                            <td>{{ $row['lastActive'] ? $row['lastActive']->timezone(config('app.timezone'))->format('M j, Y g:i A') : '—' }}</td>
                            <td class="{{ $row['online'] ? 'online' : '' }}">{{ $row['online'] ? 'Logged in' : 'Offline' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-layout>
