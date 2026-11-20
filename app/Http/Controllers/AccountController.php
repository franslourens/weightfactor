<?php

namespace App\Http\Controllers;

use App\Mail\PasswordChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
            'publicUrl' => $user->isOwner()
                ? route('dashboard')
                : route('profile.show', $user),
        ]);
    }

    /**
     * Save the signed-in user's name and password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $passwordChanged = $request->filled('password');

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        if ($passwordChanged) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validate($rules);

        $user->name = $validated['name'];

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
