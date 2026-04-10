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
        if (! $section->is_active) {
            abort(404);
        }

        $section->load('verticals');

        $articles = $section->articles()
            ->published()
            ->orderedWithinSection()
            ->with(['section', 'contributors', 'vertical'])
            ->paginate(12);

        return Inertia::render('Magazine/Section', [
            'section' => $section,
            'articles' => $articles,
        ]);
    }

    public function vertical(Section $section, Vertical $vertical)
    {
        if (! $section->is_active) {
            abort(404);
        }

        if ($vertical->section_id !== $section->id) {
            abort(404);
        }

        $articles = $vertical->articles()
            ->published()
            ->orderedWithinSection()
            ->with(['section', 'contributors'])
            ->paginate(12);

        return Inertia::render('Magazine/Vertical', [
            'section' => $section,
            'vertical' => $vertical,
            'articles' => $articles,
        ]);
    }
}
