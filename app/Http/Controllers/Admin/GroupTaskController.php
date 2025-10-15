// ...existing code...
    public function store(Request $request, Group $group)
    {
        Gate::authorize('manageTasks', $group);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group->tasks()->create($validated);

        return back();
    }

    public function update(Request $request, GroupTask $task)
    {
        Gate::authorize('manageTasks', $task->group);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_current' => 'sometimes|boolean',
        ]);

        $task->update($validated);

        return back();
    }

    public function destroy(GroupTask $task)
    {
        Gate::authorize('manageTasks', $task->group);

        $task->delete();

        return back();
    }
}
