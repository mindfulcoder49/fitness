<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Contributor;
use App\Models\Section;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MagazineArticleController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['title', 'section', 'status', 'access', 'published_at', 'contributor'];
        $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'published_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $articles = Article::with(['section', 'contributors'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->section_id, fn ($q, $id) => $q->where('section_id', $id))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                          ->orWhere('slug', 'like', "%{$search}%")
                          ->orWhere('status', 'like', "%{$search}%")
                          ->orWhere('access', 'like', "%{$search}%")
                          ->orWhereHas('section', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                          ->orWhereHas('contributors', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($sortBy === 'section', fn ($q) => $q->leftJoin('sections', 'sections.id', '=', 'articles.section_id')
                ->orderBy('sections.name', $sortDir)
                ->select('articles.*'))
            ->when($sortBy === 'contributor', fn ($q) => $q->leftJoin('article_contributor', 'article_contributor.article_id', '=', 'articles.id')
                ->leftJoin('contributors', 'contributors.id', '=', 'article_contributor.contributor_id')
                ->orderBy('contributors.name', $sortDir)
                ->select('articles.*'))
            ->when(! in_array($sortBy, ['section', 'contributor'], true), fn ($q) => $q->orderBy($sortBy, $sortDir))
            ->paginate(20)
            ->withQueryString();

        $sections = Section::orderBy('sort_order')->get(['id', 'name']);

        return Inertia::render('Admin/Magazine/Articles', [
            'articles' => $articles,
            'sections' => $sections,
            'filters'  => $request->only(['status', 'section_id', 'search', 'sort_by', 'sort_dir']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Magazine/ArticleForm', [
            'sections' => Section::with('verticals')->orderBy('sort_order')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'contributors' => Contributor::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateArticle($request);
        $validated = $this->normalizePublicationFields($validated);
        $validated['section_order'] = $this->nextSectionOrder((int) $validated['section_id']);

        if ($request->hasFile('featured_image')) {
            $validated['featured_image_path'] = $request->file('featured_image')->store('articles/images', 'public');
        }
        unset($validated['featured_image']);

        $article = Article::create($validated);

        if ($request->tags) {
            $article->tags()->sync($request->tags);
        }

        if ($request->contributors) {
            $this->syncContributors($article, $request->contributors);
        }

        return redirect()->route('admin.magazine.articles.edit', $article)
            ->with('success', 'Article created.');
    }

    public function edit(Article $article)
    {
        $article->load(['tags', 'contributors']);

        return Inertia::render('Admin/Magazine/ArticleForm', [
            'article' => $article,
            'sections' => Section::with('verticals')->orderBy('sort_order')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'contributors' => Contributor::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Article $article)
    {
        $validated = $this->validateArticle($request, $article);
        $validated = $this->normalizePublicationFields($validated);
        $newSectionId = (int) $validated['section_id'];

        if ($article->section_id !== $newSectionId || $article->section_order === null) {
            $validated['section_order'] = $this->nextSectionOrder($newSectionId, $article->id);
        }

        if ($request->hasFile('featured_image')) {
            $validated['featured_image_path'] = $request->file('featured_image')->store('articles/images', 'public');
        }
        unset($validated['featured_image']);

        $article->update($validated);

        if ($request->has('tags')) {
            $article->tags()->sync($request->tags ?? []);
        }

        if ($request->has('contributors')) {
            $this->syncContributors($article, $request->contributors ?? []);
        }

        return back()->with('success', 'Article updated.');
    }

    public function destroy(Article $article)
    {
        $article->delete();

        return redirect()->route('admin.magazine.articles.index')
            ->with('success', 'Article deleted.');
    }

    public function autosave(Request $request, Article $article)
    {
        $data = $request->validate([
            'title'            => 'sometimes|nullable|string|max:255',
            'slug'             => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('articles', 'slug')->ignore($article->id)],
            'excerpt'          => 'sometimes|nullable|string',
            'content'          => 'sometimes|nullable|string',
            'section_id'       => 'sometimes|nullable|exists:sections,id',
            'vertical_id'      => 'sometimes|nullable',
            'status'           => 'sometimes|in:draft,scheduled,published,archived',
            'access'           => 'sometimes|in:public,members',
            'is_featured'      => 'sometimes|boolean',
            'meta_title'       => 'sometimes|nullable|string|max:255',
            'meta_description' => 'sometimes|nullable|string|max:500',
            'published_at'     => 'sometimes|nullable|date',
        ]);

        if (!empty($data['slug'])) {
            $data['slug'] = $this->resolveUniqueSlug($data['slug'], $article->id);
        }

        $article->update($data);

        if ($request->has('tags')) {
            $article->tags()->sync($request->input('tags', []));
        }

        if ($request->has('contributors')) {
            $this->syncContributors($article, $request->input('contributors', []));
        }

        return response()->json(['saved_at' => now()->toISOString()]);
    }

    public function uploadImage(Request $request, Article $article)
    {
        $request->validate(['image' => 'required|image|max:5120']);

        $path = $request->file('image')->store('articles/images', 'public');

        return response()->json(['url' => \Storage::url($path)]);
    }

    public function uploadVideo(Request $request, Article $article)
    {
        $request->validate(['video' => 'required|mimes:mp4,webm,mov|max:102400']);

        $path = $request->file('video')->store('articles/video', 'public');

        return response()->json(['url' => \Storage::url($path)]);
    }

    public function uploadAudio(Request $request, Article $article)
    {
        $request->validate(['audio' => 'required|mimes:mp3,wav,ogg,m4a|max:51200']);

        $path = $request->file('audio')->store('articles/audio', 'public');

        return response()->json(['url' => \Storage::url($path)]);
    }

    private function validateArticle(Request $request, ?Article $article = null): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('articles', 'slug')->ignore($article?->id),
            ],
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:5120',
            'section_id' => 'required|exists:sections,id',
            'vertical_id' => [
                'nullable',
                Rule::exists('verticals', 'id')->where(fn ($query) => $query->where('section_id', $request->input('section_id'))),
            ],
            'status' => 'required|in:draft,scheduled,published,archived',
            'access' => 'required|in:public,members',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'published_at' => 'nullable|date',
        ]);

        $validated['slug'] = $this->resolveUniqueSlug(
            $validated['slug'] ?: $validated['title'],
            $article?->id
        );

        return $validated;
    }

    private function normalizePublicationFields(array $validated): array
    {
        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        if ($validated['status'] === 'scheduled') {
            if (empty($validated['published_at'])) {
                throw ValidationException::withMessages([
                    'published_at' => 'A publish date is required for scheduled articles.',
                ]);
            }

            if (now()->greaterThanOrEqualTo($validated['published_at'])) {
                throw ValidationException::withMessages([
                    'published_at' => 'Scheduled publish date must be in the future.',
                ]);
            }
        }

        return $validated;
    }

    private function resolveUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);

        if ($base === '') {
            $base = 'article';
        }

        $slug = $base;
        $suffix = 2;

        while (
            Article::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function syncContributors(Article $article, array $contributors): void
    {
        $sync = [];
        foreach ($contributors as $entry) {
            if (!empty($entry['contributor_id']) && !empty($entry['role'])) {
                $sync[$entry['contributor_id']] = ['role' => $entry['role']];
            }
        }
        $article->contributors()->sync($sync);
    }

    private function nextSectionOrder(int $sectionId, ?int $ignoreArticleId = null): int
    {
        return (int) Article::query()
            ->where('section_id', $sectionId)
            ->when($ignoreArticleId, fn ($query) => $query->whereKeyNot($ignoreArticleId))
            ->max('section_order') + 1;
    }
}
