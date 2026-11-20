<x-mail::message>
# Welcome to Weight Factor

Hello {{ $user->name }},

Your profile is ready. Log a weight, then update your target.

<x-mail::button :url="route('profile.show', $user)">
Open your profile
</x-mail::button>

Anyone you give that link to can see your page. The home page stays the main dashboard.

Thanks,<br>
Weight Factor
</x-mail::message>
