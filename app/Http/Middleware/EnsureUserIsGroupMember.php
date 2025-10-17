<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsGroupMember
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');

        if (!$group instanceof Group) {
            // Or handle as an error, depending on your application's needs
            return $next($request);
        }

        $user = Auth::user();

        // Allow access if the group is public
        if ($group->is_public) {
            return $next($request);
        }

        // For private groups, check for admin, creator, or membership
        if ($user) {
            if ($user->is_admin || $user->id === $group->creator_id || $user->groups()->where('group_id', $group->id)->exists()) {
                return $next($request);
            }
        }

        abort(403, 'You are not a member of this group.');
    }
}
