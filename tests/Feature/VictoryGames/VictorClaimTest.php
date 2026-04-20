<?php

namespace Tests\Feature\VictoryGames;

use App\Models\User;
use App\Models\VictoryGamesVictor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VictorClaimTest extends TestCase
{
    use RefreshDatabase;

    // ── Claim: happy path ──────────────────────────────────────────────────────

    public function test_authenticated_user_can_claim_unclaimed_profile_by_email(): void
    {
        $user = User::factory()->create();
        $victor = VictoryGamesVictor::create([
            'slug'        => 'claimable-profile',
            'display_name' => 'Claimable User',
            'email'       => 'claimable@example.com',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('victory-games.victors.claim', $victor->slug), [
                'identity' => 'claimable@example.com',
            ]);

        $response->assertRedirect(route('victory-games.victors.show', $victor->slug));
        $response->assertSessionHasNoErrors();
        $this->assertSame($user->id, $victor->fresh()->user_id);
    }

    public function test_authenticated_user_can_claim_unclaimed_profile_by_external_user_id(): void
    {
        $user   = User::factory()->create();
        $victor = VictoryGamesVictor::create([
            'slug'             => 'ext-claim-profile',
            'display_name'     => 'External Claim',
            'external_user_id' => 'ext-abc-123',
            'claim_token'      => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('victory-games.victors.claim', $victor->slug), [
                'identity' => 'ext-abc-123',
            ]);

        $response->assertRedirect(route('victory-games.victors.show', $victor->slug));
        $this->assertSame($user->id, $victor->fresh()->user_id);
    }

    // ── Claim: email case-insensitive ──────────────────────────────────────────

    public function test_claim_by_email_is_case_insensitive(): void
    {
        $user   = User::factory()->create();
        $victor = VictoryGamesVictor::create([
            'slug'        => 'case-insensitive-profile',
            'display_name' => 'Case Insensitive',
            'email'       => 'Test.User@Example.COM',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('victory-games.victors.claim', $victor->slug), [
                'identity' => 'test.user@example.com',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame($user->id, $victor->fresh()->user_id);
    }

    // ── Claim: wrong identity rejected ────────────────────────────────────────

    public function test_claim_with_wrong_identity_is_rejected(): void
    {
        $user   = User::factory()->create();
        $victor = VictoryGamesVictor::create([
            'slug'        => 'reject-profile',
            'display_name' => 'Reject Test',
            'email'       => 'correct@example.com',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('victory-games.victors.claim', $victor->slug), [
                'identity' => 'wrong@example.com',
            ]);

        $response->assertSessionHasErrors('identity');
        $this->assertNull($victor->fresh()->user_id);
    }

    // ── Claim: already-claimed profile returns 409 ────────────────────────────

    public function test_cannot_claim_an_already_claimed_profile(): void
    {
        $owner  = User::factory()->create();
        $claimant = User::factory()->create();

        $victor = VictoryGamesVictor::create([
            'slug'        => 'owned-profile',
            'display_name' => 'Already Owned',
            'user_id'     => $owner->id,
            'email'       => 'owner@example.com',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($claimant)
            ->post(route('victory-games.victors.claim', $victor->slug), [
                'identity' => 'owner@example.com',
            ]);

        $response->assertStatus(409);
    }

    // ── Claim: guest is redirected to login ───────────────────────────────────

    public function test_guest_cannot_claim_a_profile(): void
    {
        $victor = VictoryGamesVictor::create([
            'slug'        => 'guest-target',
            'display_name' => 'Guest Target',
            'email'       => 'target@example.com',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->post(route('victory-games.victors.claim', $victor->slug), [
            'identity' => 'target@example.com',
        ]);

        // Auth middleware redirects guests to login (302), not a 401 — the
        // controller-level abort_unless(auth()->check(), 401) was dead code and
        // has been removed.
        $response->assertRedirect(route('login'));
        $this->assertNull($victor->fresh()->user_id);
    }

    // ── Claim: user who already owns a profile cannot claim a second one ───────
    // BUG: there is no guard preventing this — this test SHOULD pass but is
    // expected to FAIL until the bug is fixed.

    public function test_user_who_already_has_a_victor_profile_cannot_claim_a_second_one(): void
    {
        $user = User::factory()->create();

        // Self-created profile
        VictoryGamesVictor::create([
            'user_id'      => $user->id,
            'slug'         => 'existing-profile',
            'display_name' => 'Existing',
            'email'        => $user->email,
            'claim_token'  => VictoryGamesVictor::generateClaimToken(),
        ]);

        // External unclaimed profile with matching email
        $unclaimed = VictoryGamesVictor::create([
            'slug'        => 'second-profile',
            'display_name' => 'Second Profile',
            'email'       => 'second@example.com',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('victory-games.victors.claim', $unclaimed->slug), [
                'identity' => 'second@example.com',
            ]);

        // Should be rejected (user already has a profile)
        $response->assertStatus(409);
        $this->assertNull($unclaimed->fresh()->user_id);
    }

    // ── Admin assign-user ──────────────────────────────────────────────────────

    public function test_admin_can_assign_victor_profile_to_a_user_by_username(): void
    {
        $admin  = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['username' => 'targetuser']);
        $victor = VictoryGamesVictor::create([
            'slug'        => 'assign-test',
            'display_name' => 'Assign Test',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('victory-games.victors.assign-user', $victor->slug), [
                'user_identifier' => 'targetuser',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame($target->id, $victor->fresh()->user_id);
    }

    public function test_admin_can_unlink_victor_profile_from_user(): void
    {
        $admin  = User::factory()->create(['is_admin' => true]);
        $owner  = User::factory()->create();
        $victor = VictoryGamesVictor::create([
            'user_id'     => $owner->id,
            'slug'        => 'unlink-test',
            'display_name' => 'Unlink Test',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('victory-games.victors.assign-user', $victor->slug), [
                'user_identifier' => '',
            ]);

        $response->assertRedirect();
        $this->assertNull($victor->fresh()->user_id);
    }

    public function test_admin_cannot_assign_victor_to_user_who_already_owns_a_different_profile(): void
    {
        $admin        = User::factory()->create(['is_admin' => true]);
        $existingOwner = User::factory()->create(['username' => 'existingowner']);

        VictoryGamesVictor::create([
            'user_id'     => $existingOwner->id,
            'slug'        => 'their-existing-profile',
            'display_name' => 'Their Profile',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $target = VictoryGamesVictor::create([
            'slug'        => 'unowned-target',
            'display_name' => 'Unowned Target',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('victory-games.victors.assign-user', $target->slug), [
                'user_identifier' => 'existingowner',
            ]);

        $response->assertSessionHasErrors('user_identifier');
        $this->assertNull($target->fresh()->user_id);
    }

    public function test_non_admin_cannot_use_assign_user_endpoint(): void
    {
        $user   = User::factory()->create(['is_admin' => false]);
        $victor = VictoryGamesVictor::create([
            'slug'        => 'no-assign',
            'display_name' => 'No Assign',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('victory-games.victors.assign-user', $victor->slug), [
                'user_identifier' => $user->username,
            ]);

        $response->assertForbidden();
    }

    // ── Victor deletion ────────────────────────────────────────────────────────

    public function test_owner_can_delete_their_victor_profile(): void
    {
        $user   = User::factory()->create();
        $victor = VictoryGamesVictor::create([
            'user_id'     => $user->id,
            'slug'        => 'deletable',
            'display_name' => 'Deletable',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($user)
            ->delete(route('victory-games.victors.destroy', $victor->slug));

        $response->assertRedirect(route('victory-games.home'));
        $this->assertDatabaseMissing('victory_games_victors', ['id' => $victor->id]);
    }

    public function test_admin_can_delete_any_victor_profile(): void
    {
        $admin  = User::factory()->create(['is_admin' => true]);
        $owner  = User::factory()->create();
        $victor = VictoryGamesVictor::create([
            'user_id'     => $owner->id,
            'slug'        => 'admin-deletable',
            'display_name' => 'Admin Deletable',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('victory-games.victors.destroy', $victor->slug));

        $response->assertRedirect(route('victory-games.victors'));
        $this->assertDatabaseMissing('victory_games_victors', ['id' => $victor->id]);
    }

    public function test_non_owner_cannot_delete_another_users_victor_profile(): void
    {
        $attacker = User::factory()->create();
        $owner    = User::factory()->create();
        $victor   = VictoryGamesVictor::create([
            'user_id'     => $owner->id,
            'slug'        => 'protected-victor',
            'display_name' => 'Protected',
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);

        $response = $this->actingAs($attacker)
            ->delete(route('victory-games.victors.destroy', $victor->slug));

        $response->assertForbidden();
        $this->assertDatabaseHas('victory_games_victors', ['id' => $victor->id]);
    }
}
