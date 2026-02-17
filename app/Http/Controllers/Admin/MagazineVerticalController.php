<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vertical;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MagazineVerticalController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:verticals',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('verticals', 'public');
        }

        unset($validated['image']);
        Vertical::create($validated);

        return back()->with('success', 'Vertical created.');
    }

    public function update(Request $request, Vertical $vertical)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:verticals,slug,' . $vertical->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('verticals', 'public');
        }

        unset($validated['image']);
        $vertical->update($validated);

        return back()->with('success', 'Vertical updated.');
    }

    public function destroy(Vertical $vertical)
    {
        $vertical->delete();

        return back()->with('success', 'Vertical deleted.');
    }
}
