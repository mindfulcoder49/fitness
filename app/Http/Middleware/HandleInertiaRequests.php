<?php

namespace App\Http\Middleware;

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
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        $response = parent::handle($request, $next);

        if (! $response->headers->has('Vary')) {
            $response->headers->set('Vary', 'Accept');
        }

        if ($response->headers->get('Vary') !== 'Accept, X-Inertia') {
            $response->headers->set('Vary', trim($response->headers->get('Vary').', X-Inertia'), true);
        }

        return $response;
    }

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
                ] : null,
            ],
        ]);
    }
}
