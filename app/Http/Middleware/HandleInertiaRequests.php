<?php

namespace App\Http\Middleware;

use App\Models\Section;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'can_post_images' => $user->can_post_images,
                    'can_post_videos' => $user->can_post_videos,
                    'theme' => $user->theme ?? 'light',
                ] : null,
            ],
            'appName' => config('app.name'),
            'appUrl' => config('app.url'),
            'logoUrl' => function () {
                $path = SiteSetting::get('site_logo_path');
                return $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
            },
            'magazineSections' => fn () => Section::active()->get(['id', 'name', 'slug']),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'features' => [
                'groups_enabled' => fn () => filter_var(SiteSetting::get('groups_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            ],
        ]);
    }
}
