<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_set_current_group_task(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $group = Group::create([
            'creator_id' => $user->id,
            'name' => 'Alpha',
            'is_public' => true,
        ]);
        $task = GroupTask::create([
            'group_id' => $group->id,
            'title' => 'Task 1',
            'is_current' => false,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.groups.tasks.set-current', $task))
            ->assertForbidden();
    }

    public function test_admin_set_current_unsets_other_current_tasks_in_same_group(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $group = Group::create([
            'creator_id' => $admin->id,
            'name' => 'Alpha',
            'is_public' => true,
        ]);
        $oldCurrentTask = GroupTask::create([
            'group_id' => $group->id,
            'title' => 'Old Current',
            'is_current' => true,
        ]);
        $newCurrentTask = GroupTask::create([
            'group_id' => $group->id,
            'title' => 'New Current',
            'is_current' => false,
        ]);

        $this->actingAs($admin)
            ->from('/admin')
            ->patch(route('admin.groups.tasks.set-current', $newCurrentTask))
            ->assertRedirect('/admin');

        $this->assertTrue($newCurrentTask->fresh()->is_current);
        $this->assertFalse($oldCurrentTask->fresh()->is_current);
    }

    public function test_admin_can_unset_current_task(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $group = Group::create([
            'creator_id' => $admin->id,
            'name' => 'Alpha',
            'is_public' => true,
        ]);
        $task = GroupTask::create([
            'group_id' => $group->id,
            'title' => 'Current Task',
            'is_current' => true,
        ]);

        $this->actingAs($admin)
            ->from('/admin')
            ->patch(route('admin.groups.tasks.unset-current', $task))
            ->assertRedirect('/admin');

        $this->assertFalse($task->fresh()->is_current);
    }
}
