<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GroupAndPostFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_group_creation_forces_private_group(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Private by Default',
            'description' => 'desc',
            'is_public' => true,
        ]);

        $group = Group::first();

        $this->assertNotNull($group);
        $this->assertFalse((bool) $group->is_public);
        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
    }

    public function test_user_cannot_leave_group_they_created(): void
    {
        $creator = User::factory()->create();
        $group = Group::create([
            'creator_id' => $creator->id,
            'name' => 'Alpha',
            'is_public' => true,
        ]);
        $group->users()->attach($creator->id, ['role' => 'admin']);

        $this->actingAs($creator)
            ->from('/dashboard')
            ->post(route('groups.leave', $group))
            ->assertRedirect('/dashboard');

        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $creator->id,
        ]);
    }

    public function test_user_can_join_public_group_and_membership_is_created(): void
    {
        $creator = User::factory()->create();
        $joiner = User::factory()->create();
        $group = Group::create([
            'creator_id' => $creator->id,
            'name' => 'Public Group',
            'is_public' => true,
        ]);
        $group->users()->attach($creator->id, ['role' => 'admin']);

        $this->actingAs($joiner)
            ->post(route('groups.join', $group))
            ->assertRedirect(route('groups.show', $group, false));

        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $joiner->id,
            'role' => 'member',
        ]);
    }

    public function test_joining_private_group_is_forbidden(): void
    {
        $creator = User::factory()->create();
        $joiner = User::factory()->create();
        $group = Group::create([
            'creator_id' => $creator->id,
            'name' => 'Private Group',
            'is_public' => false,
        ]);
        $group->users()->attach($creator->id, ['role' => 'admin']);

        $this->actingAs($joiner)
            ->post(route('groups.join', $group))
            ->assertForbidden();
    }

    public function test_group_launches_when_minimum_members_reached_on_join(): void
    {
        Mail::fake();

        $creator = User::factory()->create();
        $joiner = User::factory()->create();
        $group = Group::create([
            'creator_id' => $creator->id,
            'name' => 'Threshold Group',
            'is_public' => true,
            'min_members' => 2,
            'launched_at' => null,
        ]);
        $group->users()->attach($creator->id, ['role' => 'admin']);

        $this->actingAs($joiner)->post(route('groups.join', $group));

        $this->assertNotNull($group->fresh()->launched_at);
    }

    public function test_non_member_cannot_create_post_for_group(): void
    {
        $creator = User::factory()->create();
        $outsider = User::factory()->create();
        $group = Group::create([
            'creator_id' => $creator->id,
            'name' => 'Posting Group',
            'is_public' => true,
        ]);
        $group->users()->attach($creator->id, ['role' => 'admin']);

        $this->actingAs($outsider)
            ->post(route('posts.store'), [
                'content' => 'Hello',
                'group_id' => $group->id,
            ])
            ->assertForbidden();
    }

    public function test_member_can_create_text_post(): void
    {
        $user = User::factory()->create();
        $group = Group::create([
            'creator_id' => $user->id,
            'name' => 'Posting Group',
            'is_public' => true,
        ]);
        $group->users()->attach($user->id, ['role' => 'admin']);

        $this->actingAs($user)
            ->from('/groups/' . $group->id)
            ->post(route('posts.store'), [
                'content' => 'Daily update',
                'group_id' => $group->id,
            ])
            ->assertRedirect('/groups/' . $group->id);

        $this->assertDatabaseHas('posts', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'content' => 'Daily update',
        ]);
    }

    public function test_member_without_image_permission_cannot_upload_image_post(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension not installed.');
        }

        Storage::fake('public');

        $user = User::factory()->create(['can_post_images' => false]);
        $group = Group::create([
            'creator_id' => $user->id,
            'name' => 'Posting Group',
            'is_public' => true,
        ]);
        $group->users()->attach($user->id, ['role' => 'admin']);

        $response = $this->actingAs($user)
            ->from('/groups/' . $group->id)
            ->post(route('posts.store'), [
                'content' => '',
                'group_id' => $group->id,
                'image' => UploadedFile::fake()->image('photo.jpg'),
            ]);

        $response->assertRedirect('/groups/' . $group->id);
        $response->assertSessionHasErrors('image');
        $this->assertDatabaseCount('posts', 0);
    }
}
