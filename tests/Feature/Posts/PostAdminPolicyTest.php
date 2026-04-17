<?php

namespace Tests\Feature\Posts;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the PostPolicy edge cases, specifically the site admin flag
 * vs group-admin membership distinction.
 */
class PostAdminPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function makeGroupWithAdminAndMember(): array
    {
        $admin  = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();

        $group = Group::create([
            'creator_id' => $admin->id,
            'name'       => 'Policy Test Group',
            'is_public'  => false,
        ]);
        $group->users()->attach($admin->id, ['role' => 'admin']);
        $group->users()->attach($member->id, ['role' => 'member']);

        return [$admin, $member, $group];
    }

    // ── Site admin who is NOT in the group ────────────────────────────────────
    // BUG: PostPolicy::delete does not check $user->is_admin, so a site admin
    // who is not a member of the group cannot delete posts via PostController.

    public function test_site_admin_not_in_group_can_delete_any_post(): void
    {
        $groupCreator = User::factory()->create(['is_admin' => true]);
        $siteAdmin    = User::factory()->create(['is_admin' => true]);
        $author       = User::factory()->create();

        $group = Group::create([
            'creator_id' => $groupCreator->id,
            'name'       => 'Admin Delete Test Group',
            'is_public'  => false,
        ]);
        $group->users()->attach($groupCreator->id, ['role' => 'admin']);
        $group->users()->attach($author->id, ['role' => 'member']);
        // siteAdmin is NOT in the group

        $post = $author->posts()->create([
            'group_id' => $group->id,
            'content'  => 'Post to be deleted by site admin.',
        ]);

        $response = $this->actingAs($siteAdmin)->delete(route('posts.destroy', $post));

        // Site admin should be able to delete any post — BUG: this returns 403
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    // ── Group admin CAN delete posts from their group ─────────────────────────

    public function test_group_admin_can_delete_any_post_in_their_group(): void
    {
        [$admin, $member, $group] = $this->makeGroupWithAdminAndMember();

        $post = $member->posts()->create([
            'group_id' => $group->id,
            'content'  => 'Post to be deleted.',
        ]);

        $response = $this->actingAs($admin)->delete(route('posts.destroy', $post));

        $response->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    // ── Regular member cannot delete other members' posts ────────────────────

    public function test_regular_member_cannot_delete_another_members_post(): void
    {
        [$admin, $member, $group] = $this->makeGroupWithAdminAndMember();
        $otherMember = User::factory()->create();
        $group->users()->attach($otherMember->id, ['role' => 'member']);

        $post = $member->posts()->create([
            'group_id' => $group->id,
            'content'  => 'Protected post.',
        ]);

        $response = $this->actingAs($otherMember)->delete(route('posts.destroy', $post));

        $response->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    // ── Site admin update: PostPolicy::update also doesn't check is_admin ────
    // BUG: PostPolicy::update only allows the post author, not site admins.

    public function test_site_admin_not_in_group_can_update_any_post(): void
    {
        $groupCreator = User::factory()->create(['is_admin' => true]);
        $siteAdmin    = User::factory()->create(['is_admin' => true]);
        $author       = User::factory()->create();

        $group = Group::create([
            'creator_id' => $groupCreator->id,
            'name'       => 'Admin Update Test Group',
            'is_public'  => false,
        ]);
        $group->users()->attach($groupCreator->id, ['role' => 'admin']);
        $group->users()->attach($author->id, ['role' => 'member']);
        // siteAdmin is NOT in the group

        $post = $author->posts()->create([
            'group_id' => $group->id,
            'content'  => 'Original content.',
        ]);

        $response = $this->actingAs($siteAdmin)->patch(route('posts.update', $post), [
            'content' => 'Admin-edited content.',
        ]);

        // Site admin should be able to edit any post — BUG: returns 403
        $response->assertRedirect();
        $this->assertSame('Admin-edited content.', $post->fresh()->content);
    }

    // ── Comment deletion ─────────────────────────────────────────────────────

    public function test_comment_author_can_delete_their_own_comment(): void
    {
        [$admin, $member, $group] = $this->makeGroupWithAdminAndMember();

        $post = $member->posts()->create([
            'group_id' => $group->id,
            'content'  => 'Post with comment.',
        ]);

        $comment = $post->comments()->create([
            'user_id' => $member->id,
            'content' => 'My comment.',
        ]);

        $response = $this->actingAs($member)->delete(route('comments.destroy', $comment));

        $response->assertRedirect();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_another_users_comment(): void
    {
        [$admin, $member, $group] = $this->makeGroupWithAdminAndMember();
        $otherMember = User::factory()->create();
        $group->users()->attach($otherMember->id, ['role' => 'member']);

        $post = $member->posts()->create([
            'group_id' => $group->id,
            'content'  => 'Post.',
        ]);

        $comment = $post->comments()->create([
            'user_id' => $member->id,
            'content' => 'Protected comment.',
        ]);

        $response = $this->actingAs($otherMember)->delete(route('comments.destroy', $comment));

        $response->assertForbidden();
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }
}
