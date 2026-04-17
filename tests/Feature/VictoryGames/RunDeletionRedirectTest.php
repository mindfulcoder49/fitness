<?php

namespace Tests\Feature\VictoryGames;

use App\Models\User;
use App\Models\VictoryGamesApp;
use App\Models\VictoryGamesCompetition;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesVictor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests for run deletion and the redirect logic in RunDetailController::destroy.
 */
class RunDeletionRedirectTest extends TestCase
{
    use RefreshDatabase;

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

    private function makeApp(VictoryGamesVictor $owner, string $name): VictoryGamesApp
    {
        $app = VictoryGamesApp::create([
            'name'        => $name,
            'slug'        => VictoryGamesApp::generateSlug($name),
            'current_url' => 'https://example.com',
        ]);
        $app->victors()->attach($owner->id, ['role' => 'owner']);
        return $app;
    }

    private function makeEntry(VictoryGamesVictor $victor, User $user, array $overrides = []): VictoryGamesEntry
    {
        return VictoryGamesEntry::create(array_merge([
            'victor_id'           => $victor->id,
            'started_by_user_id'  => $user->id,
            'external_entry_id'   => (string) Str::ulid(),
            'external_user_id'    => 'native-user-'.$user->id,
            'run_origin'          => 'native',
            'app_url'             => 'https://example.com',
            'app_goal'            => 'Test goal.',
            'app_mode'            => 'desktop',
            'session_provider'    => 'openai',
            'session_model'       => 'gpt-5-mini',
            'session_status'      => 'completed',
            'session_external_id' => (string) Str::uuid(),
            'run_config'          => ['max_steps' => 5],
            'submitted_at'        => now(),
            'completed_at'        => now(),
        ], $overrides));
    }

    // ── Active runs cannot be deleted ────────────────────────────────────────

    public function test_queued_run_cannot_be_deleted(): void
    {
        [$user, $victor] = $this->makeUserWithVictor('Queued Victor');
        $app   = $this->makeApp($victor, 'Queued App');
        $entry = $this->makeEntry($victor, $user, [
            'session_status' => 'queued',
            'completed_at'   => null,
        ]);

        $response = $this->actingAs($user)->delete(route('victory-games.runs.destroy', $entry));

        $response->assertForbidden();
        $this->assertDatabaseHas('victory_games_entries', ['id' => $entry->id]);
    }

    public function test_running_run_cannot_be_deleted(): void
    {
        [$user, $victor] = $this->makeUserWithVictor('Running Victor');
        $app   = $this->makeApp($victor, 'Running App');
        $entry = $this->makeEntry($victor, $user, [
            'session_status' => 'running',
            'started_at'     => now()->subMinute(),
            'completed_at'   => null,
        ]);

        $response = $this->actingAs($user)->delete(route('victory-games.runs.destroy', $entry));

        $response->assertForbidden();
        $this->assertDatabaseHas('victory_games_entries', ['id' => $entry->id]);
    }

    // ── Completed runs can be deleted ────────────────────────────────────────

    public function test_completed_run_with_app_redirects_to_app_page_after_deletion(): void
    {
        [$user, $victor] = $this->makeUserWithVictor('App Run Victor');
        $app   = $this->makeApp($victor, 'Redirect App');
        $entry = $this->makeEntry($victor, $user, ['app_id' => $app->id]);

        // Delete from the run detail page (referer = run URL)
        $response = $this->actingAs($user)
            ->from(route('victory-games.runs.show', $entry))
            ->delete(route('victory-games.runs.destroy', $entry));

        $response->assertRedirect(route('victory-games.apps.show', $app->slug));
        $this->assertDatabaseMissing('victory_games_entries', ['id' => $entry->id]);
    }

    public function test_completed_run_without_app_redirects_to_victor_page_after_deletion(): void
    {
        [$user, $victor] = $this->makeUserWithVictor('Solo Run Victor');
        $entry = $this->makeEntry($victor, $user);  // no app_id

        // Delete from the run detail page
        $response = $this->actingAs($user)
            ->from(route('victory-games.runs.show', $entry))
            ->delete(route('victory-games.runs.destroy', $entry));

        $response->assertRedirect(route('victory-games.victors.show', $victor->slug));
        $this->assertDatabaseMissing('victory_games_entries', ['id' => $entry->id]);
    }

    // ── Deleting from app page redirects back to app page ────────────────────
    // BUG: when deleting from a non-run page, the previous URL is the app page.
    // The controller checks if previous URL is not the run URL and calls back().
    // This means deletion from the app page correctly redirects back to the app page.
    // But deletion without any referer redirects to http://localhost (site root).

    public function test_deleting_run_without_referer_redirects_to_app_page_not_site_root(): void
    {
        [$user, $victor] = $this->makeUserWithVictor('No Referer Victor');
        $app   = $this->makeApp($victor, 'No Referer App');
        $entry = $this->makeEntry($victor, $user, ['app_id' => $app->id]);

        // No from() call — no referer header
        $response = $this->actingAs($user)
            ->delete(route('victory-games.runs.destroy', $entry));

        // Should redirect to the app page (where the run is visible)
        // BUG: actually redirects to http://localhost (site root)
        $response->assertRedirect(route('victory-games.apps.show', $app->slug));
        $this->assertDatabaseMissing('victory_games_entries', ['id' => $entry->id]);
    }

    // ── Other user cannot delete someone else's run ───────────────────────────

    public function test_other_user_cannot_delete_another_users_run(): void
    {
        [$owner, $ownerVictor] = $this->makeUserWithVictor('Run Owner');
        [$attackerUser] = $this->makeUserWithVictor('Attacker');
        $entry = $this->makeEntry($ownerVictor, $owner);

        $response = $this->actingAs($attackerUser)
            ->from(route('victory-games.runs.show', $entry))
            ->delete(route('victory-games.runs.destroy', $entry));

        $response->assertForbidden();
        $this->assertDatabaseHas('victory_games_entries', ['id' => $entry->id]);
    }

    public function test_guest_cannot_delete_a_run(): void
    {
        [$owner, $ownerVictor] = $this->makeUserWithVictor('Guest Target Owner');
        $entry = $this->makeEntry($ownerVictor, $owner);

        $response = $this->delete(route('victory-games.runs.destroy', $entry));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('victory_games_entries', ['id' => $entry->id]);
    }

    // ── Admin can delete any run ───────────────────────────────────────────────

    public function test_admin_can_delete_any_completed_run(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        [$owner, $ownerVictor] = $this->makeUserWithVictor('Admin Target Owner');
        $entry = $this->makeEntry($ownerVictor, $owner);

        $response = $this->actingAs($admin)
            ->from(route('victory-games.runs.show', $entry))
            ->delete(route('victory-games.runs.destroy', $entry));

        $response->assertRedirect();
        $this->assertDatabaseMissing('victory_games_entries', ['id' => $entry->id]);
    }
}
