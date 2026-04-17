<?php

namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests for Group creation, joining, and auto-launch logic.
 */
class GroupLaunchAndJoinTest extends TestCase
{
    use RefreshDatabase;

    // ── Group creation ────────────────────────────────────────────────────────

    public function test_admin_can_create_a_public_group(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('groups.store'), [
            'name'        => 'Public Admin Group',
            'description' => 'Visible to everyone.',
            'is_public'   => true,
            'min_members' => null,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $group = Group::first();
        $this->assertTrue((bool) $group->is_public);
    }

    // ── Non-admin cannot create public groups ─────────────────────────────────
    // The controller silently forces is_public=false for non-admins.
    // BUG: no validation error is returned — the user gets a private group
    // when they requested a public one with no feedback.

    public function test_non_admin_requesting_public_group_gets_a_private_group(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name'        => 'Sneaky Public Group',
            'description' => 'Trying to be public.',
            'is_public'   => true,
            'min_members' => null,
        ]);

        $response->assertRedirect();
        $group = Group::first();
        // Group is forced private — but user was not told about it
        $this->assertFalse((bool) $group->is_public);
    }

    public function test_non_admin_creating_group_is_added_as_admin_member(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name'        => 'My Group',
            'description' => null,
            'is_public'   => false,
            'min_members' => null,
        ]);

        $response->assertRedirect();
        $group = Group::first();
        $this->assertTrue($group->isUserAdmin($user));
    }

    // ── Joining a public group ─────────────────────────────────────────────────

    public function test_authenticated_user_can_join_a_public_group(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $joiner  = User::factory()->create();

        $group = Group::create([
            'creator_id' => $creator->id,
            'name'       => 'Public Join Group',
            'is_public'  => true,
        ]);
        $group->users()->attach($creator->id, ['role' => 'admin']);

        $response = $this->actingAs($joiner)->post(route('groups.join', $group));

        $response->assertRedirect(route('groups.show', $group));
        $this->assertTrue($group->users()->where('user_id', $joiner->id)->exists());
    }

    public function test_user_cannot_join_a_private_group_via_join_endpoint(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $joiner  = User::factory()->create();

        $group = Group::create([
            'creator_id' => $creator->id,
            'name'       => 'Private Group',
            'is_public'  => false,
        ]);
        $group->users()->attach($creator->id, ['role' => 'admin']);

        $response = $this->actingAs($joiner)->post(route('groups.join', $group));

        $response->assertForbidden();
        $this->assertFalse($group->users()->where('user_id', $joiner->id)->exists());
    }

    // ── Auto-launch on min_members threshold ──────────────────────────────────

    public function test_group_auto_launches_when_min_members_threshold_is_reached(): void
    {
        Mail::fake();

        $creator = User::factory()->create(['is_admin' => true]);
        $joiners = User::factory()->count(3)->create();

        $group = Group::create([
            'creator_id'  => $creator->id,
            'name'        => 'Launch Test Group',
            'is_public'   => true,
            'min_members' => 4,  // 1 creator + 3 joiners = 4 total → should launch
        ]);
        $group->users()->attach($creator->id, ['role' => 'admin']);

        // Two joiners bring count to 3 — not yet launched
        $this->actingAs($joiners[0])->post(route('groups.join', $group));
        $this->actingAs($joiners[1])->post(route('groups.join', $group));
        $this->assertNull($group->fresh()->launched_at);

        // Third joiner hits the threshold (total = 4)
        $this->actingAs($joiners[2])->post(route('groups.join', $group));
        $this->assertNotNull($group->fresh()->launched_at);
    }

    public function test_group_without_min_members_is_considered_launched_immediately(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);

        $group = Group::create([
            'creator_id'  => $creator->id,
            'name'        => 'No Min Group',
            'is_public'   => true,
            'min_members' => null,
        ]);

        $this->assertTrue($group->isLaunched());
    }

    public function test_group_with_min_members_not_yet_met_is_not_launched(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $joiner  = User::factory()->create();

        $group = Group::create([
            'creator_id'  => $creator->id,
            'name'        => 'Unlaunched Group',
            'is_public'   => true,
            'min_members' => 10,
        ]);
        $group->users()->attach($creator->id, ['role' => 'admin']);

        $this->actingAs($joiner)->post(route('groups.join', $group));

        $this->assertFalse($group->fresh()->isLaunched());
    }

    // ── Joining triggers auto-launch only once ────────────────────────────────

    public function test_already_launched_group_does_not_re_trigger_launch_on_new_join(): void
    {
        Mail::fake();

        $creator = User::factory()->create(['is_admin' => true]);
        $joiners = User::factory()->count(4)->create();

        $group = Group::create([
            'creator_id'  => $creator->id,
            'name'        => 'Already Launched Group',
            'is_public'   => true,
            'min_members' => 2,
        ]);
        $group->users()->attach($creator->id, ['role' => 'admin']);

        // First joiner triggers launch
        $this->actingAs($joiners[0])->post(route('groups.join', $group));
        $this->assertNotNull($group->fresh()->launched_at);
        $launchedAt = $group->fresh()->launched_at;

        // Subsequent joiners should not change launched_at
        $this->actingAs($joiners[1])->post(route('groups.join', $group));
        $this->assertEquals($launchedAt->toDateTimeString(), $group->fresh()->launched_at->toDateTimeString());
    }

    // ── Creator cannot leave their own group ─────────────────────────────────

    public function test_creator_cannot_leave_their_own_group(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $group   = Group::create([
            'creator_id' => $creator->id,
            'name'       => 'Creator Group',
            'is_public'  => false,
        ]);
        $group->users()->attach($creator->id, ['role' => 'admin']);

        $response = $this->actingAs($creator)->post(route('groups.leave', $group));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertTrue($group->users()->where('user_id', $creator->id)->exists());
    }

    // ── Public group preview accessible to guests ─────────────────────────────

    public function test_public_group_preview_is_accessible_to_guests(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $group   = Group::create([
            'creator_id' => $creator->id,
            'name'       => 'Preview Group',
            'is_public'  => true,
        ]);

        $response = $this->get(route('groups.preview', $group));

        $response->assertOk();
    }

    public function test_private_group_preview_returns_404(): void
    {
        $creator = User::factory()->create(['is_admin' => true]);
        $group   = Group::create([
            'creator_id' => $creator->id,
            'name'       => 'Private Preview Group',
            'is_public'  => false,
        ]);

        $response = $this->get(route('groups.preview', $group));

        $response->assertNotFound();
    }
}
