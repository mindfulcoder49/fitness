<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\GroupLaunched;
use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): Response
    {
        $group = null;

        if ($request->query('group')) {
            $foundGroup = Group::find($request->query('group'));
            if ($foundGroup) {
                $group = [
                    'id' => $foundGroup->id,
                    'name' => $foundGroup->name,
                ];
            }
        }

        return Inertia::render('Auth/Register', [
            'group' => $group,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:'.User::class,
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'group' => 'nullable|integer|exists:groups,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        if ($request->group) {
            $group = Group::find($request->group);
            if ($group) {
                $group->users()->attach($user->id, ['role' => 'member']);

                if (!$group->isLaunched() && $group->min_members && $group->users()->count() >= $group->min_members) {
                    $group->update(['launched_at' => now()]);
                    $group->load('users');

                    foreach ($group->users as $member) {
                        try {
                            Mail::to($member->email)->send(new GroupLaunched($group));
                        } catch (\Exception $e) {
                            Log::error('Registration group launch: Failed to send email', [
                                'member_email' => $member->email,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                return redirect(route('groups.show', $group, absolute: false));
            }
        }

        return redirect(route('dashboard', absolute: false));
    }
}
