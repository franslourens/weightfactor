<?php

namespace App\Http\Controllers;

use App\Mail\PasswordChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AccountController extends Controller
{
    /**
     * Show the signed-in user's name and password.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $user->ensureSlug();

        return view('account', [
            'user' => $user,
            'publicUrl' => route('profile.show', $user),
        ]);
    }

    /**
     * Save the signed-in user's name and password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $passwordChanged = $request->filled('password');
        $request->merge([
            'username' => Str::slug($request->string('username')->toString()),
        ]);

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'username' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('users', 'slug')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        if ($passwordChanged) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validate($rules, [
            'username.required' => 'Choose a username using letters and numbers.',
            'username.regex' => 'Choose a username using letters and numbers.',
            'username.unique' => 'That username is already taken.',
        ]);

        $user->name = $validated['name'];
        $user->slug = $validated['username'];

        if ($passwordChanged) {
            $user->password = $validated['password'];
        }

        $user->save();

        if ($passwordChanged) {
            try {
                Mail::to($user)->send(new PasswordChanged($user));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return back()->with('status', 'Account saved.');
    }
}
