<?php

namespace App\Http\Controllers\Magazine;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function show(Article $article)
    {
        $isPublished = Article::published()
            ->whereKey($article->id)
            ->exists();

        if (! $isPublished) {
            abort(404);
        }

        $article->load(['section', 'vertical', 'tags', 'contributors']);

        $isMembersOnly = $article->access === 'members' && !Auth::check();
        if ($isMembersOnly) {
            $teaserText = $article->excerpt ?: Str::of(strip_tags($article->content))->squish()->limit(280)->toString();
            $article->setAttribute('content', '<p>' . e($teaserText) . '</p>');
        }

        return Inertia::render('Magazine/Article', [
            'article' => $article,
            'isMembersOnly' => $isMembersOnly,
        ]);
    }
}
