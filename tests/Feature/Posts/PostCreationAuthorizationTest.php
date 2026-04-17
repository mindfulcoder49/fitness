<?php

namespace Tests\Feature\Posts;

use App\Models\Group;
use App\Models\GroupTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for post creation authorization and validation.
 */
class PostCreationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makePublicGroup(User $creator, array $attrs = []): Group
    {
        $group = Group::create(array_merge([
            'creator_id'  => $creator->id,
            'name'        => 'Test Group',
            'description' => 'A test group.',
            'is_public'   => true,
        ], $attrs));

        $group->users()->attach($creator->id, ['role' => 'admin']);

        return $group;
    }

    // ── Member can post ───────────────────────────────────────────────────────

    public function test_group_member_can_create_a_post(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $member  = User::factory()->create();
        $group   = $this->makePublicGroup($creator);
        $group->users()->attach($member->id, ['role' => 'member']);

        $response = $this->actingAs($member)->post(route('posts.store'), [
            'group_id' => $group->id,
            'content'  => 'Hello from a group member!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('posts', [
            'group_id' => $group->id,
            'user_id'  => $member->id,
            'content'  => 'Hello from a group member!',
        ]);
    }

    // ── Non-member posting to a group ─────────────────────────────────────────
    // BUG: PostController uses $user->groups()->findOrFail() which throws a 404
    // ModelNotFoundException instead of returning a 403 for non-members.

    public function test_non_member_cannot_post_to_a_group(): void
    {
        $creator    = User::factory()->create(['is_admin' => true]);
        $nonMember  = User::factory()->create();
        $group      = $this->makePublicGroup($creator);

        $response = $this->actingAs($nonMember)->post(route('posts.store'), [
            'group_id' => $group->id,
            'content'  => 'Sneaky post from non-member.',
        ]);

        // Should return 403 (forbidden), not 404 (not found)
        $response->assertForbidden();
        $this->assertDatabaseMissing('posts', [
            'group_id' => $group->id,
            'user_id'  => $nonMember->id,
        ]);
    }

    // ── Guest cannot post ─────────────────────────────────────────────────────

    public function test_guest_cannot_create_a_post(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $group   = $this->makePublicGroup($creator);

        $response = $this->post(route('posts.store'), [
            'group_id' => $group->id,
            'content'  => 'Guest post attempt.',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('posts', 0);
    }

    // ── Post requires either content or a media file ──────────────────────────

    public function test_post_without_content_or_media_is_rejected(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $group   = $this->makePublicGroup($creator);

        $response = $this->actingAs($creator)->post(route('posts.store'), [
            'group_id' => $group->id,
            'content'  => null,
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('posts', 0);
    }

    // ── Image upload blocked without permission ───────────────────────────────

    public function test_user_without_image_permission_cannot_post_an_image(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension not installed.');
        }

        $creator = User::factory()->create(['is_admin' => true]);
        $member  = User::factory()->create(['can_post_images' => false]);
        $group   = $this->makePublicGroup($creator);
        $group->users()->attach($member->id, ['role' => 'member']);

        $fakeImage = \Illuminate\Http\UploadedFile::fake()->image('photo.jpg', 100, 100);

        $response = $this->actingAs($member)->post(route('posts.store'), [
            'group_id' => $group->id,
            'image'    => $fakeImage,
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseCount('posts', 0);
    }

    // ── Task-linked posts ─────────────────────────────────────────────────────

    public function test_member_can_post_against_a_group_task(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $member  = User::factory()->create();
        $group   = $this->makePublicGroup($creator);
        $group->users()->attach($member->id, ['role' => 'member']);

        $task = GroupTask::create([
            'group_id'   => $group->id,
            'title'      => 'Daily Challenge',
            'is_current' => true,
        ]);

        $response = $this->actingAs($member)->post(route('posts.store'), [
            'group_id'      => $group->id,
            'group_task_id' => $task->id,
            'content'       => 'Completed the challenge!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('posts', [
            'group_id'      => $group->id,
            'group_task_id' => $task->id,
            'user_id'       => $member->id,
        ]);
    }

    // ── Task from different group is blocked ──────────────────────────────────
    // A task that exists but belongs to a different group should be rejected.

    public function test_task_from_different_group_is_rejected(): void
    {
        $creator   = User::factory()->create(['is_admin' => true]);
        $member    = User::factory()->create();
        $group     = $this->makePublicGroup($creator);
        $group->users()->attach($member->id, ['role' => 'member']);

        $otherCreator = User::factory()->create(['is_admin' => true]);
        $otherGroup   = $this->makePublicGroup($otherCreator, ['name' => 'Other Group']);

        $foreignTask = GroupTask::create([
            'group_id'   => $otherGroup->id,
            'title'      => 'Foreign Task',
            'is_current' => true,
        ]);

        $response = $this->actingAs($member)->post(route('posts.store'), [
            'group_id'      => $group->id,
            'group_task_id' => $foreignTask->id,
            'content'       => 'Attempting to associate a foreign task.',
        ]);

        // Should be rejected (task doesn't belong to this group)
        $response->assertStatus(404);
        $this->assertDatabaseMissing('posts', [
            'group_id'      => $group->id,
            'group_task_id' => $foreignTask->id,
        ]);
    }

    // ── Daily limit for task ──────────────────────────────────────────────────

    public function test_user_cannot_post_twice_for_the_same_task_in_one_day(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $member  = User::factory()->create();
        $group   = $this->makePublicGroup($creator);
        $group->users()->attach($member->id, ['role' => 'member']);

        $task = GroupTask::create([
            'group_id'   => $group->id,
            'title'      => 'Daily Task',
            'is_current' => true,
        ]);

        // First post succeeds
        $this->actingAs($member)->post(route('posts.store'), [
            'group_id'      => $group->id,
            'group_task_id' => $task->id,
            'content'       => 'First submission today.',
        ]);

        // Second post on the same day should fail
        $response = $this->actingAs($member)->post(route('posts.store'), [
            'group_id'      => $group->id,
            'group_task_id' => $task->id,
            'content'       => 'Second submission, should be blocked.',
        ]);

        $response->assertSessionHasErrors('daily_limit');
        $this->assertDatabaseCount('posts', 1);
    }

    // ── Post update authorization ─────────────────────────────────────────────

    public function test_post_author_can_update_their_own_post(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $member  = User::factory()->create();
        $group   = $this->makePublicGroup($creator);
        $group->users()->attach($member->id, ['role' => 'member']);

        $post = $member->posts()->create([
            'group_id' => $group->id,
            'content'  => 'Original content.',
        ]);

        $response = $this->actingAs($member)->patch(route('posts.update', $post), [
            'content' => 'Updated content.',
        ]);

        $response->assertRedirect();
        $this->assertSame('Updated content.', $post->fresh()->content);
    }

    public function test_user_cannot_update_another_users_post(): void
    {
        $creator    = User::factory()->create(['is_admin' => true]);
        $author     = User::factory()->create();
        $attacker   = User::factory()->create();
        $group      = $this->makePublicGroup($creator);
        $group->users()->attach($author->id, ['role' => 'member']);
        $group->users()->attach($attacker->id, ['role' => 'member']);

        $post = $author->posts()->create([
            'group_id' => $group->id,
            'content'  => 'Author original.',
        ]);

        $response = $this->actingAs($attacker)->patch(route('posts.update', $post), [
            'content' => 'Attacker override.',
        ]);

        $response->assertForbidden();
        $this->assertSame('Author original.', $post->fresh()->content);
    }

    // ── Post delete authorization ─────────────────────────────────────────────

    public function test_post_author_can_delete_their_post(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $member  = User::factory()->create();
        $group   = $this->makePublicGroup($creator);
        $group->users()->attach($member->id, ['role' => 'member']);

        $post = $member->posts()->create([
            'group_id' => $group->id,
            'content'  => 'To be deleted.',
        ]);

        $response = $this->actingAs($member)->delete(route('posts.destroy', $post));

        $response->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_admin_can_delete_any_post(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $member  = User::factory()->create();
        $group   = $this->makePublicGroup($creator);
        $group->users()->attach($member->id, ['role' => 'member']);

        $post = $member->posts()->create([
            'group_id' => $group->id,
            'content'  => 'Admin-deleted post.',
        ]);

        $response = $this->actingAs($creator)->delete(route('posts.destroy', $post));

        $response->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
