<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MagazineSectionController extends Controller
{
    public function index()
    {
        $sections = Section::withCount('articles')
            ->with(['verticals' => fn ($q) => $q->withCount('articles')])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Admin/Magazine/Sections', [
            'sections' => $sections,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:sections',
            'description' => 'nullable|string',
            'homepage_article_limit' => 'required|integer|min:0|max:12',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = $this->resolveUniqueSlug($validated['slug'] ?: $validated['name']);

        Section::create($validated);

        return back()->with('success', 'Section created.');
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:sections,slug,' . $section->id,
            'description' => 'nullable|string',
            'homepage_article_limit' => 'required|integer|min:0|max:12',
            'is_active' => 'boolean',
        ]);

        $section->update($validated);

        return back()->with('success', 'Section updated.');
    }

    public function destroy(Section $section)
    {
        $section->delete();

        return back()->with('success', 'Section deleted.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:sections,id',
        ]);

        foreach ($validated['order'] as $index => $id) {
            Section::where('id', $id)->update(['sort_order' => $index]);
        }

        return back()->with('success', 'Sections reordered.');
    }

    public function articles(Section $section)
    {
        $articles = $section->articles()
            ->with(['vertical'])
            ->orderedWithinSection()
            ->get();

        return Inertia::render('Admin/Magazine/SectionArticles', [
            'section' => $section,
            'articles' => $articles,
        ]);
    }

    public function reorderArticles(Request $request, Section $section)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:articles,id',
        ]);

        $sectionArticleIds = $section->articles()->pluck('id')->all();
        $submittedOrder = array_values(array_unique($validated['order']));

        if (
            count($submittedOrder) !== count($sectionArticleIds) ||
            array_diff($sectionArticleIds, $submittedOrder) ||
            array_diff($submittedOrder, $sectionArticleIds)
        ) {
            throw ValidationException::withMessages([
                'order' => 'Article order must include every article in the section exactly once.',
            ]);
        }

        foreach ($submittedOrder as $index => $articleId) {
            Article::whereKey($articleId)->update(['section_order' => $index + 1]);
        }

        return back()->with('success', 'Article order updated.');
    }

    private function resolveUniqueSlug(string $value): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'section';
        }

        $slug = $base;
        $suffix = 2;

        while (Section::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
