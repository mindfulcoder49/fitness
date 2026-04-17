<?php

namespace Tests\Feature\VictoryGames;

use App\Models\User;
use App\Models\VictoryGamesApp;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesVictor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests for VictoryGames App authorization.
 *
 * Covers the `requireAppEdit` boundary, which uses `isMember` (any role).
 * Only app owners (and admins) should be able to modify app details and
 * manage team membership. Non-owner members should be restricted.
 */
class AppMemberAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function makeUserWithVictor(string $name): array
    {
        $user   = User::factory()->create();
        $victor = VictoryGamesVictor::create([
            'user_id'     => $user->id,
            'slug'        => VictoryGamesVictor::generateSlug($name),
            'display_name' => $name,
            'email'       => $user->email,
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);
        return [$user, $victor];
    }

    private function makeAppOwnedBy(VictoryGamesVictor $owner, string $name): VictoryGamesApp
    {
        $app = VictoryGamesApp::create([
            'name'        => $name,
            'slug'        => VictoryGamesApp::generateSlug($name),
            'current_url' => 'https://example.com',
        ]);
        $app->victors()->attach($owner->id, ['role' => 'owner']);
        return $app;
    }

    // ── Create app ────────────────────────────────────────────────────────────

    public function test_victor_can_create_an_app(): void
    {
        [$user, $victor] = $this->makeUserWithVictor('App Creator');

        $response = $this->actingAs($user)->post(route('victory-games.apps.store'), [
            'name'        => 'My Startup',
            'description' => 'An app for testing.',
            'current_url' => 'https://mystartup.com',
        ]);

        $app = VictoryGamesApp::first();
        $response->assertRedirect(route('victory-games.apps.show', $app->slug));
        $this->assertDatabaseHas('victory_games_app_victor', [
            'app_id'    => $app->id,
            'victor_id' => $victor->id,
            'role'      => 'owner',
        ]);
    }

    public function test_user_without_victor_profile_cannot_create_an_app(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('victory-games.apps.store'), [
            'name' => 'Forbidden App',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('victory_games_apps', 0);
    }

    // ── Owner can update app ───────────────────────────────────────────────────

    public function test_owner_can_update_app_details(): void
    {
        [$user, $victor] = $this->makeUserWithVictor('App Owner');
        $app = $this->makeAppOwnedBy($victor, 'Original Name');

        $response = $this->actingAs($user)
            ->patch(route('victory-games.apps.update', $app->slug), [
                'name'        => 'Updated Name',
                'description' => 'New description.',
                'current_url' => 'https://updated.com',
            ]);

        $response->assertRedirect(route('victory-games.apps.show', $app->fresh()->slug));
        $this->assertSame('Updated Name', $app->fresh()->name);
    }

    // ── Non-owner member SHOULD NOT be able to update app ─────────────────────
    // BUG: requireAppEdit uses isMember (any role), allowing this. Should be isOwnedBy.

    public function test_non_owner_member_cannot_update_app_details(): void
    {
        [$ownerUser, $ownerVictor] = $this->makeUserWithVictor('Real Owner');
        [$memberUser, $memberVictor] = $this->makeUserWithVictor('Just A Member');
        $app = $this->makeAppOwnedBy($ownerVictor, 'Protected App');
        $app->victors()->attach($memberVictor->id, ['role' => 'member']);

        $response = $this->actingAs($memberUser)
            ->patch(route('victory-games.apps.update', $app->slug), [
                'name'        => 'Hijacked Name',
                'description' => 'Hijacked description.',
                'current_url' => 'https://hijacked.com',
            ]);

        $response->assertForbidden();
        $this->assertSame('Protected App', $app->fresh()->name);
    }

    // ── Non-owner member SHOULD NOT be able to add members ───────────────────

    public function test_non_owner_member_cannot_add_new_members_to_app(): void
    {
        [$ownerUser, $ownerVictor] = $this->makeUserWithVictor('App Owner 2');
        [$memberUser, $memberVictor] = $this->makeUserWithVictor('Existing Member');
        [, $outsiderVictor] = $this->makeUserWithVictor('Outsider');
        $app = $this->makeAppOwnedBy($ownerVictor, 'Member Add Test App');
        $app->victors()->attach($memberVictor->id, ['role' => 'member']);

        $response = $this->actingAs($memberUser)
            ->post(route('victory-games.apps.members.add', $app->slug), [
                'victor_slug' => $outsiderVictor->slug,
            ]);

        $response->assertForbidden();
        $this->assertFalse($app->isMember($outsiderVictor));
    }

    // ── Owner can add members ─────────────────────────────────────────────────

    public function test_owner_can_add_a_new_member(): void
    {
        [$ownerUser, $ownerVictor] = $this->makeUserWithVictor('Adding Owner');
        [, $newVictor] = $this->makeUserWithVictor('New Team Member');
        $app = $this->makeAppOwnedBy($ownerVictor, 'Add Member App');

        $response = $this->actingAs($ownerUser)
            ->post(route('victory-games.apps.members.add', $app->slug), [
                'victor_slug' => $newVictor->slug,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertTrue($app->fresh()->isMember($newVictor));
    }

    // ── Adding an already-existing member shows misleading success ────────────
    // BUG: returns "X added to team" even when the victor was already a member.

    public function test_adding_already_existing_member_does_not_give_misleading_success(): void
    {
        [$ownerUser, $ownerVictor] = $this->makeUserWithVictor('Owner Adder');
        [, $existingMember] = $this->makeUserWithVictor('Already There');
        $app = $this->makeAppOwnedBy($ownerVictor, 'Dupe Member App');
        $app->victors()->attach($existingMember->id, ['role' => 'member']);

        $response = $this->actingAs($ownerUser)
            ->post(route('victory-games.apps.members.add', $app->slug), [
                'victor_slug' => $existingMember->slug,
            ]);

        // Response should not claim "added to team" if they were already there.
        // Currently this returns a 'success' flash even when nothing changed.
        $response->assertSessionMissing('success');
    }

    // ── Non-owner member SHOULD NOT be able to remove members ────────────────

    public function test_non_owner_member_cannot_remove_other_members(): void
    {
        [$ownerUser, $ownerVictor] = $this->makeUserWithVictor('App Owner 3');
        [$memberUser, $memberVictor] = $this->makeUserWithVictor('Member Who Tries Remove');
        [, $victimVictor] = $this->makeUserWithVictor('Victim Member');
        $app = $this->makeAppOwnedBy($ownerVictor, 'Remove Test App');
        $app->victors()->attach($memberVictor->id, ['role' => 'member']);
        $app->victors()->attach($victimVictor->id, ['role' => 'member']);

        $response = $this->actingAs($memberUser)
            ->delete(route('victory-games.apps.members.remove', [$app->slug, $victimVictor->slug]));

        $response->assertForbidden();
        $this->assertTrue($app->fresh()->isMember($victimVictor));
    }

    // ── Cannot remove the only owner ─────────────────────────────────────────

    public function test_cannot_remove_the_only_owner_from_an_app(): void
    {
        [$ownerUser, $ownerVictor] = $this->makeUserWithVictor('Solo Owner');
        $app = $this->makeAppOwnedBy($ownerVictor, 'Solo Owner App');

        $response = $this->actingAs($ownerUser)
            ->delete(route('victory-games.apps.members.remove', [$app->slug, $ownerVictor->slug]));

        $response->assertRedirect();
        $response->assertSessionHasErrors('member');
        $this->assertTrue($app->fresh()->isMember($ownerVictor));
    }

    // ── Owner can remove a non-owner member ───────────────────────────────────

    public function test_owner_can_remove_a_member(): void
    {
        [$ownerUser, $ownerVictor] = $this->makeUserWithVictor('Owner Remover');
        [, $memberVictor] = $this->makeUserWithVictor('Removable Member');
        $app = $this->makeAppOwnedBy($ownerVictor, 'Remove Member App');
        $app->victors()->attach($memberVictor->id, ['role' => 'member']);

        $response = $this->actingAs($ownerUser)
            ->delete(route('victory-games.apps.members.remove', [$app->slug, $memberVictor->slug]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertFalse($app->fresh()->isMember($memberVictor));
    }

    // ── Guest is blocked from all write operations ────────────────────────────

    public function test_guest_cannot_create_an_app(): void
    {
        $response = $this->post(route('victory-games.apps.store'), [
            'name' => 'Guest App',
        ]);

        $response->assertRedirect(route('login'));
    }

    // ── Assign run ────────────────────────────────────────────────────────────

    public function test_victor_can_assign_their_own_run_to_one_of_their_apps(): void
    {
        [$user, $victor] = $this->makeUserWithVictor('Run Assigner');
        $app = $this->makeAppOwnedBy($victor, 'Assignment Target App');

        $entry = VictoryGamesEntry::create([
            'victor_id'          => $victor->id,
            'started_by_user_id' => $user->id,
            'external_entry_id'  => (string) Str::ulid(),
            'external_user_id'   => 'native-user-'.$user->id,
            'run_origin'         => 'native',
            'app_url'            => 'https://example.com',
            'app_goal'           => 'Test the homepage.',
            'app_mode'           => 'desktop',
            'session_provider'   => 'openai',
            'session_model'      => 'gpt-5-mini',
            'session_status'     => 'completed',
            'session_external_id' => (string) Str::uuid(),
            'run_config'         => ['max_steps' => 5],
            'submitted_at'       => now(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('victory-games.runs.assign-app', $entry->id), [
                'app_id' => $app->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame($app->id, $entry->fresh()->app_id);
    }

    public function test_victor_cannot_assign_their_run_to_an_app_they_do_not_belong_to(): void
    {
        [$user, $victor] = $this->makeUserWithVictor('Assigner Victor');
        [, $otherVictor] = $this->makeUserWithVictor('Other Owner');
        $foreignApp = $this->makeAppOwnedBy($otherVictor, 'Foreign App');

        $entry = VictoryGamesEntry::create([
            'victor_id'          => $victor->id,
            'started_by_user_id' => $user->id,
            'external_entry_id'  => (string) Str::ulid(),
            'external_user_id'   => 'native-user-'.$user->id,
            'run_origin'         => 'native',
            'app_url'            => 'https://example.com',
            'app_goal'           => 'Test the homepage.',
            'app_mode'           => 'desktop',
            'session_provider'   => 'openai',
            'session_model'      => 'gpt-5-mini',
            'session_status'     => 'completed',
            'session_external_id' => (string) Str::uuid(),
            'run_config'         => ['max_steps' => 5],
            'submitted_at'       => now(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('victory-games.runs.assign-app', $entry->id), [
                'app_id' => $foreignApp->id,
            ]);

        $response->assertForbidden();
        $this->assertNull($entry->fresh()->app_id);
    }
}
