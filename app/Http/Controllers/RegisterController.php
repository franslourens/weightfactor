<?php

namespace App\Http\Controllers;

use App\Mail\Welcome;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        $user->profile()->create(Profile::defaults());

        try {
            Mail::to($user)->send(new Welcome($user));
        } catch (Throwable $exception) {
            report($exception);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('profile.show', $user);
    }
}
