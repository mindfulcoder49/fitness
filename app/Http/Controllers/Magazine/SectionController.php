<?php

namespace App\Http\Controllers\Magazine;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Vertical;
use Inertia\Inertia;

class SectionController extends Controller
{
    public function show(Section $section)
    {
        $section->load('verticals');

        $articles = $section->articles()
            ->published()
            ->with(['section', 'contributors', 'vertical'])
            ->latest('published_at')
            ->paginate(12);

        return Inertia::render('Magazine/Section', [
            'section' => $section,
            'articles' => $articles,
        ]);
    }

    public function vertical(Section $section, Vertical $vertical)
    {
        if ($vertical->section_id !== $section->id) {
            abort(404);
        }

        $articles = $vertical->articles()
            ->published()
            ->with(['section', 'contributors'])
            ->latest('published_at')
            ->paginate(12);

        return Inertia::render('Magazine/Vertical', [
            'section' => $section,
            'vertical' => $vertical,
            'articles' => $articles,
        ]);
    }
}
