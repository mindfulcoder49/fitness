<?php

namespace Tests\Feature\VictoryGames;

use App\Jobs\RunVictoryGamesNativeAiuxJob;
use App\Models\User;
use App\Models\VictoryGamesApp;
use App\Models\VictoryGamesCompetition;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesVictor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class NativeAiuxRunTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_user_can_create_a_victor_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('victory-games.victors.store'), [
            'display_name' => 'Test Builder',
            'bio' => 'Builds apps for native AIUX runs.',
            'github_url' => 'https://github.com/test-builder',
        ]);

        $victor = VictoryGamesVictor::first();

        $response->assertRedirect(route('victory-games.victors.show', $victor));
        $response->assertSessionHasNoErrors();

        $this->assertNotNull($victor);
        $this->assertSame($user->id, $victor->user_id);
        $this->assertSame('Test Builder', $victor->display_name);
        $this->assertNotEmpty($victor->claim_token);
    }

    public function test_user_without_victor_profile_cannot_queue_a_native_run(): void
    {
        config()->set('victory_games.native_runs.providers.openai.enabled', true);

        $user = User::factory()->create();
        $owner = User::factory()->create();
        $ownerVictor = $this->createVictorFor($owner, 'Owner Victor');
        $app = $this->createAppFor($ownerVictor, 'Native Run App');

        $response = $this->actingAs($user)->post(route('victory-games.apps.runs.store', $app->slug), [
            'goal' => 'Find the pricing page.',
            'start_url' => 'https://example.com',
            'mode' => 'desktop',
            'provider' => 'openai',
            'model' => 'gpt-5-mini',
            'max_steps' => 12,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('victory_games_entries', 0);
    }

    public function test_team_member_can_queue_a_native_run_and_dispatch_the_worker_job(): void
    {
        Queue::fake();

        config()->set('victory_games.native_runs.providers.openai.enabled', true);
        config()->set('victory_games.native_runs.providers.openai.default_model', 'gpt-5-mini');

        $owner = User::factory()->create();
        $ownerVictor = $this->createVictorFor($owner, 'Owner Victor');
        $app = $this->createAppFor($ownerVictor, 'Native Queue App');

        $member = User::factory()->create();
        $memberVictor = $this->createVictorFor($member, 'Member Victor');
        $app->victors()->attach($memberVictor->id, ['role' => 'member']);

        $response = $this->actingAs($member)->post(route('victory-games.apps.runs.store', $app->slug), [
            'goal' => 'Inspect the homepage and understand the primary call to action.',
            'start_url' => 'https://example.com',
            'mode' => 'desktop',
            'provider' => 'openai',
            'model' => 'gpt-5-mini',
            'max_steps' => 12,
        ]);

        $entry = VictoryGamesEntry::first();

        $response->assertRedirect(route('victory-games.runs.show', $entry));
        $response->assertSessionHasNoErrors();

        $this->assertNotNull($entry);
        $this->assertSame('native', $entry->run_origin);
        $this->assertSame('queued', $entry->session_status);
        $this->assertSame($app->id, $entry->app_id);
        $this->assertSame($memberVictor->id, $entry->victor_id);
        $this->assertSame($member->id, $entry->started_by_user_id);
        $this->assertSame('openai', $entry->session_provider);
        $this->assertSame('gpt-5-mini', $entry->session_model);
        $this->assertSame(12, $entry->run_config['max_steps']);

        Queue::assertPushed(RunVictoryGamesNativeAiuxJob::class, 1);
    }

    public function test_owner_can_stop_an_active_native_run(): void
    {
        $user = User::factory()->create();
        $victor = $this->createVictorFor($user, 'Stopping Victor');
        $app = $this->createAppFor($victor, 'Stop App');
        $entry = $this->createNativeEntry($victor, $app, [
            'started_by_user_id' => $user->id,
            'session_status' => 'running',
            'started_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->post(route('victory-games.runs.stop', $entry));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $entry->refresh();

        $this->assertSame('stopped', $entry->session_status);
        $this->assertSame('Stop requested by user.', $entry->end_reason);
    }

    public function test_active_native_run_must_be_stopped_before_it_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $victor = $this->createVictorFor($user, 'Delete Guard Victor');
        $app = $this->createAppFor($victor, 'Delete Guard App');
        $entry = $this->createNativeEntry($victor, $app, [
            'started_by_user_id' => $user->id,
            'session_status' => 'running',
            'started_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->delete(route('victory-games.runs.destroy', $entry));

        $response->assertForbidden();
        $this->assertDatabaseHas('victory_games_entries', ['id' => $entry->id]);
    }

    public function test_owner_can_delete_their_own_competition_run(): void
    {
        $user = User::factory()->create();
        $victor = $this->createVictorFor($user, 'Competition Victor');
        $app = $this->createAppFor($victor, 'Competition App');
        $competition = VictoryGamesCompetition::create([
            'external_id' => (string) Str::uuid(),
            'slug' => 'spring-showdown',
            'name' => 'Spring Showdown',
            'status' => 'complete',
            'held_at' => now(),
        ]);
        $entry = $this->createNativeEntry($victor, $app, [
            'competition_id' => $competition->id,
            'started_by_user_id' => $user->id,
            'session_status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('victory-games.runs.show', $entry))
            ->delete(route('victory-games.runs.destroy', $entry));

        // Entry has an app, so after deleting from the run detail page the
        // controller redirects to the app page (not the victor page).
        $response->assertRedirect(route('victory-games.apps.show', $app));
        $this->assertDatabaseMissing('victory_games_entries', ['id' => $entry->id]);
    }

    private function createVictorFor(User $user, string $displayName): VictoryGamesVictor
    {
        return VictoryGamesVictor::create([
            'user_id' => $user->id,
            'slug' => VictoryGamesVictor::generateSlug($displayName),
            'display_name' => $displayName,
            'email' => $user->email,
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);
    }

    private function createAppFor(VictoryGamesVictor $victor, string $name): VictoryGamesApp
    {
        $app = VictoryGamesApp::create([
            'name' => $name,
            'slug' => VictoryGamesApp::generateSlug($name),
            'current_url' => 'https://example.com',
        ]);

        $app->victors()->attach($victor->id, ['role' => 'owner']);

        return $app;
    }

    private function createNativeEntry(VictoryGamesVictor $victor, VictoryGamesApp $app, array $overrides = []): VictoryGamesEntry
    {
        return VictoryGamesEntry::create(array_merge([
            'competition_id' => null,
            'app_id' => $app->id,
            'victor_id' => $victor->id,
            'external_entry_id' => (string) Str::ulid(),
            'external_user_id' => $victor->external_user_id ?: 'native-user-'.$victor->id,
            'run_origin' => 'native',
            'app_url' => $app->current_url,
            'app_goal' => 'Verify the app loads and exposes its main CTA.',
            'app_mode' => 'desktop',
            'session_provider' => 'openai',
            'session_model' => 'gpt-5-mini',
            'session_status' => 'queued',
            'session_external_id' => (string) Str::uuid(),
            'run_config' => ['max_steps' => 8],
            'submitted_at' => now(),
        ], $overrides));
    }
}
