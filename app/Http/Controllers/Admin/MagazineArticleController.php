<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Contributor;
use App\Models\Section;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MagazineArticleController extends Controller
{
    public function index(Request $request)
    {
        $articles = Article::with(['section', 'contributors'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->section_id, fn ($q, $id) => $q->where('section_id', $id))
            ->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $sections = Section::orderBy('sort_order')->get(['id', 'name']);

        return Inertia::render('Admin/Magazine/Articles', [
            'articles' => $articles,
            'sections' => $sections,
            'filters' => $request->only(['status', 'section_id', 'search']),
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
        return $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:articles,slug,' . ($article?->id ?? 'NULL'),
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:5120',
            'section_id' => 'required|exists:sections,id',
            'vertical_id' => 'nullable|exists:verticals,id',
            'status' => 'required|in:draft,scheduled,published,archived',
            'access' => 'required|in:public,members',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'published_at' => 'nullable|date',
        ]);
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
}
