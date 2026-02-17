<?php

namespace App\Http\Controllers\Magazine;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function show(Article $article)
    {
        if ($article->status !== 'published' || $article->published_at > now()) {
            abort(404);
        }

        $article->load(['section', 'vertical', 'tags', 'contributors']);

        $isMembersOnly = $article->access === 'members' && !Auth::check();

        return Inertia::render('Magazine/Article', [
            'article' => $article,
            'isMembersOnly' => $isMembersOnly,
        ]);
    }
}
