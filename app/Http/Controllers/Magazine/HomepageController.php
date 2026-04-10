<?php

namespace App\Http\Controllers\Magazine;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Section;
use Inertia\Inertia;

class HomepageController extends Controller
{
    public function __invoke()
    {
        $featured = Article::published()
            ->featured()
            ->with(['section', 'contributors'])
            ->latest('published_at')
            ->first();

        $sections = Section::active()->get();

        $sections->each(function (Section $section) {
            $articles = collect();

            if ($section->homepage_article_limit > 0) {
                $articles = $section->articles()
                    ->published()
                    ->orderedWithinSection()
                    ->with(['section', 'contributors'])
                    ->limit($section->homepage_article_limit)
                    ->get();
            }

            $section->setRelation('articles', $articles);
        });

        return Inertia::render('Magazine/Home', [
            'featured' => $featured,
            'sections' => $sections,
        ]);
    }
}
