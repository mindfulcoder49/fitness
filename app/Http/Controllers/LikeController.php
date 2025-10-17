<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function store(Request $request, Post $post = null)
    {
        if ($request->has(['likeable_id', 'likeable_type'])) {
            $request->validate([
                'likeable_id' => 'required|integer',
                'likeable_type' => 'required|string|in:App\Models\Post,App\Models\Comment',
            ]);
            $likeable = $request->likeable_type::findOrFail($request->likeable_id);
        } elseif ($post) {
            $likeable = $post;
        } else {
            abort(400, 'Missing likeable entity.');
        }

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
