<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contributor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MagazineContributorController extends Controller
{
    public function index()
    {
        $contributors = Contributor::withCount('articles')
            ->with('user:id,name,email')
            ->orderBy('name')
            ->get();

        $users = User::select('id', 'name', 'email')
            ->whereDoesntHave('contributor')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Magazine/Contributors', [
            'contributors' => $contributors,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:contributors',
            'bio' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'photo' => 'nullable|image|max:2048',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('contributors', 'public');
        }

        unset($validated['photo']);
        Contributor::create($validated);

        return back()->with('success', 'Contributor created.');
    }

    public function update(Request $request, Contributor $contributor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:contributors,slug,' . $contributor->id,
            'bio' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('contributors', 'public');
        }

        unset($validated['photo']);
        $contributor->update($validated);

        return back()->with('success', 'Contributor updated.');
    }

    public function destroy(Contributor $contributor)
    {
        $contributor->delete();

        return back()->with('success', 'Contributor deleted.');
    }
}
