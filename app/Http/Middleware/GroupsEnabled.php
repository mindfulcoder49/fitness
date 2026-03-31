<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GroupsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!filter_var(SiteSetting::get('groups_enabled', '1'), FILTER_VALIDATE_BOOLEAN)) {
            abort(404);
        }

        return $next($request);
    }
}
