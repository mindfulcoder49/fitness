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
            return $this->checkPreLaunch($request, $next, $group, $user);
        }

        // For private groups, check for admin, creator, or membership
        if ($user) {
            if ($user->is_admin || $user->id === $group->creator_id || $user->groups()->where('group_id', $group->id)->exists()) {
                return $this->checkPreLaunch($request, $next, $group, $user);
            }
        }

        abort(403, 'You are not a member of this group.');
    }

    protected function checkPreLaunch(Request $request, Closure $next, Group $group, $user): Response
    {
        if (!$group->isLaunched()) {
            $routeName = $request->route()->getName();
            $allowedRoutes = ['groups.show', 'groups.admin'];

            if (!in_array($routeName, $allowedRoutes)) {
                if (!$user || !$group->isUserAdmin($user)) {
                    return redirect()->route('groups.show', $group)
                        ->with('error', 'This group has not launched yet. Waiting for more members to join.');
                }
            }
        }

        return $next($request);
    }
}
