<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MagazineTagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('articles')->orderBy('name')->get();

        return Inertia::render('Admin/Magazine/Tags', [
            'tags' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        Tag::create($validated);

        return back()->with('success', 'Tag created.');
    }

    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tags,slug,' . $tag->id,
        ]);

        $tag->update($validated);

        return back()->with('success', 'Tag updated.');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return back()->with('success', 'Tag deleted.');
    }
}
