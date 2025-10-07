<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'likeable_id' => 'required|integer',
            'likeable_type' => 'required|string|in:App\Models\Post,App\Models\Comment',
        ]);

        $likeable = $request->likeable_type::findOrFail($request->likeable_id);

        $like = $likeable->likes()->where('user_id', Auth::id())->first();

        if ($like) {
            // Unlike
            $like->delete();
        } else {
            // Like
            $likeable->likes()->create(['user_id' => Auth::id()]);
        }

        return back();
    }
}
