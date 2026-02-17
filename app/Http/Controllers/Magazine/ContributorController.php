<?php

namespace App\Http\Controllers\Magazine;

use App\Http\Controllers\Controller;
use App\Models\Contributor;
use Inertia\Inertia;

class ContributorController extends Controller
{
    public function show(Contributor $contributor)
    {
        $articles = $contributor->articles()
            ->published()
            ->with(['section', 'contributors'])
            ->latest('published_at')
            ->paginate(12);

        return Inertia::render('Magazine/Contributor', [
            'contributor' => $contributor,
            'articles' => $articles,
        ]);
    }
}
