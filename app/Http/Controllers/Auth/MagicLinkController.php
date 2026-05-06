<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MagicLinkToken;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MagicLinkController extends Controller
{
    public function send(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $throttleKey = 'magic-link:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => __('Too many requests. Please try again in :seconds seconds.', ['seconds' => $seconds]),
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $user = User::where('email', $request->email)->first();

        // Always return the same response to avoid user enumeration
        if ($user) {
            MagicLinkToken::where('email', $user->email)->delete();

            $plainToken = Str::random(64);

            MagicLinkToken::create([
                'email'      => $user->email,
                'token'      => Hash::make($plainToken),
                'expires_at' => Carbon::now()->addMinutes(15),
            ]);

            $url = route('magic-link.login', ['token' => $plainToken, 'email' => $user->email]);

            $user->notify(new MagicLinkNotification($url));
        }

        return back()->with('magic_link_status', 'If an account exists for that email, a sign-in link has been sent.');
    }

    public function login(Request $request): RedirectResponse
    {
        $email = $request->query('email', '');
        $plainToken = $request->query('token', '');

        $record = MagicLinkToken::where('email', $email)->first();

        if (! $record || ! Hash::check($plainToken, $record->token) || $record->isExpired()) {
            return redirect()->route('login')->withErrors([
                'email' => 'This magic link is invalid or has expired. Please request a new one.',
            ]);
        }

        $record->delete();

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'No account found for this email address.',
            ]);
        }

        Auth::login($user);

        $request->session()->regenerate();

        $destination = $user->victoryGamesVictor
            ? route('dashboard', absolute: false)
            : route('onboarding.show', absolute: false);

        return redirect()->intended($destination);
    }
}
