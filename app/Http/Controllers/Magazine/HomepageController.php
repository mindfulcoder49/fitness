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

        $sections = Section::active()
            ->with(['articles' => function ($query) {
                $query->published()
                    ->with(['section', 'contributors'])
                    ->latest('published_at')
                    ->limit(4);
            }])
            ->get();

        return Inertia::render('Magazine/Home', [
            'featured' => $featured,
            'sections' => $sections,
        ]);
    }
}
