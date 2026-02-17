<?php

namespace App\Http\Controllers\Magazine;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Inertia\Inertia;

class TagController extends Controller
{
    public function show(Tag $tag)
    {
        $articles = $tag->articles()
            ->published()
            ->with(['section', 'contributors'])
            ->latest('published_at')
            ->paginate(12);

        return Inertia::render('Magazine/Tag', [
            'tag' => $tag,
            'articles' => $articles,
        ]);
    }
}
