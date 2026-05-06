<?php

namespace Tests\Feature\VictoryGames;

use App\Jobs\RunVictoryGamesNativeAiuxJob;
use App\Models\User;
use App\Models\VictoryGamesApp;
use App\Models\VictoryGamesVictor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    // ── GET /onboarding ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_onboarding(): void
    {
        $this->get(route('onboarding.show'))->assertRedirect(route('login'));
    }

    public function test_user_without_victor_sees_onboarding_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('onboarding.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('VictoryGames/Onboarding'));
    }

    public function test_user_with_victor_is_redirected_away_from_onboarding(): void
    {
        $user = User::factory()->create();
        $victor = $this->createVictorFor($user, 'Already Set Up');

        $this->actingAs($user)
            ->get(route('onboarding.show'))
            ->assertRedirect(route('victory-games.victors.show', $victor->slug));
    }

    // ── Dashboard redirect ─────────────────────────────────────────────────────

    public function test_dashboard_redirects_user_without_victor_to_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding.show'));
    }

    public function test_dashboard_is_accessible_when_user_has_victor(): void
    {
        $user = User::factory()->create();
        $this->createVictorFor($user, 'Has Victor');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_dashboard_is_accessible_when_onboarding_is_dismissed_in_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['onboarding_dismissed' => true])
            ->get(route('dashboard'))
            ->assertOk();
    }

    // ── POST /onboarding/victor ────────────────────────────────────────────────

    public function test_storeVictor_creates_victor_and_returns_slug(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('onboarding.victor'), ['display_name' => 'New Tester']);

        $response->assertOk()
            ->assertJsonStructure(['victor' => ['slug', 'display_name']]);

        $this->assertDatabaseHas('victory_games_victors', [
            'user_id' => $user->id,
            'display_name' => 'New Tester',
        ]);
    }

    public function test_storeVictor_is_idempotent_if_victor_already_exists(): void
    {
        $user = User::factory()->create();
        $existing = $this->createVictorFor($user, 'Existing Victor');

        $response = $this->actingAs($user)
            ->postJson(route('onboarding.victor'), ['display_name' => 'Different Name']);

        $response->assertOk()
            ->assertJsonPath('victor.slug', $existing->slug);

        $this->assertDatabaseCount('victory_games_victors', 1);
    }

    public function test_storeVictor_requires_display_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('onboarding.victor'), ['display_name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['display_name']);
    }

    public function test_storeVictor_requires_auth(): void
    {
        $this->postJson(route('onboarding.victor'), ['display_name' => 'Test'])
            ->assertUnauthorized();
    }

    // ── POST /onboarding/app ───────────────────────────────────────────────────

    public function test_storeApp_creates_app_owned_by_victor(): void
    {
        $user = User::factory()->create();
        $victor = $this->createVictorFor($user, 'App Owner');

        $response = $this->actingAs($user)->postJson(route('onboarding.app'), [
            'name' => 'My SaaS',
            'current_url' => 'https://example.com',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['app' => ['slug', 'name', 'current_url']]);

        $app = VictoryGamesApp::first();
        $this->assertNotNull($app);
        $this->assertSame('My SaaS', $app->name);
        $this->assertTrue($app->isOwnedBy($victor));
    }

    public function test_storeApp_requires_victor_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('onboarding.app'), [
            'name' => 'My SaaS',
            'current_url' => 'https://example.com',
        ])->assertStatus(422);
    }

    public function test_storeApp_requires_valid_url(): void
    {
        $user = User::factory()->create();
        $this->createVictorFor($user, 'URL Tester');

        $this->actingAs($user)->postJson(route('onboarding.app'), [
            'name' => 'Bad URL App',
            'current_url' => 'not-a-url',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['current_url']);
    }

    public function test_storeApp_requires_auth(): void
    {
        $this->postJson(route('onboarding.app'), [
            'name' => 'Test',
            'current_url' => 'https://example.com',
        ])->assertUnauthorized();
    }

    // ── POST /onboarding/run ───────────────────────────────────────────────────

    public function test_storeRun_queues_job_and_redirects_to_run_detail(): void
    {
        Queue::fake();
        config()->set('victory_games.native_runs.providers.openai.enabled', true);

        $user = User::factory()->create();
        $victor = $this->createVictorFor($user, 'Run Launcher');
        $app = $this->createAppFor($victor, 'Launch App');

        $response = $this->actingAs($user)->post(route('onboarding.run'), [
            'app_slug'  => $app->slug,
            'goal'      => 'Find the sign-up button.',
            'start_url' => 'https://example.com',
            'mode'      => 'desktop',
            'provider'  => 'openai',
            'model'     => 'gpt-5-mini',
            'max_steps' => 8,
        ]);

        $entry = $app->entries()->first();
        $this->assertNotNull($entry);
        $response->assertRedirect(route('victory-games.runs.show', $entry));

        Queue::assertPushed(RunVictoryGamesNativeAiuxJob::class);

        $this->assertSame('Find the sign-up button.', $entry->app_goal);
        $this->assertSame($victor->id, $entry->victor_id);
        $this->assertSame('queued', $entry->session_status);
    }

    public function test_storeRun_requires_victor_profile(): void
    {
        config()->set('victory_games.native_runs.providers.openai.enabled', true);

        $owner = User::factory()->create();
        $ownerVictor = $this->createVictorFor($owner, 'Owner');
        $app = $this->createAppFor($ownerVictor, 'Owned App');

        $noVictor = User::factory()->create();

        $this->actingAs($noVictor)->post(route('onboarding.run'), [
            'app_slug'  => $app->slug,
            'goal'      => 'Test something.',
            'start_url' => 'https://example.com',
            'mode'      => 'desktop',
            'provider'  => 'openai',
            'model'     => 'gpt-5-mini',
            'max_steps' => 8,
        ])->assertForbidden();
    }

    public function test_storeRun_forbids_non_member_from_running(): void
    {
        Queue::fake();
        config()->set('victory_games.native_runs.providers.openai.enabled', true);

        $owner = User::factory()->create();
        $ownerVictor = $this->createVictorFor($owner, 'Owner');
        $app = $this->createAppFor($ownerVictor, 'Private App');

        $outsider = User::factory()->create();
        $this->createVictorFor($outsider, 'Outsider');

        $this->actingAs($outsider)->post(route('onboarding.run'), [
            'app_slug'  => $app->slug,
            'goal'      => 'Test something.',
            'start_url' => 'https://example.com',
            'mode'      => 'desktop',
            'provider'  => 'openai',
            'model'     => 'gpt-5-mini',
            'max_steps' => 8,
        ])->assertForbidden();

        Queue::assertNothingPushed();
    }

    // ── POST /onboarding/skip ──────────────────────────────────────────────────

    public function test_skip_sets_session_flag_and_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('onboarding.skip'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('onboarding_dismissed', true);
    }

    public function test_after_skip_dashboard_is_accessible_without_victor(): void
    {
        $user = User::factory()->create();

        // Skip first
        $this->actingAs($user)->post(route('onboarding.skip'));

        // Dashboard should now load without redirect
        $this->actingAs($user)
            ->withSession(['onboarding_dismissed' => true])
            ->get(route('dashboard'))
            ->assertOk();
    }

    // ── Registration redirect ──────────────────────────────────────────────────

    public function test_new_registration_without_invite_redirects_to_onboarding(): void
    {
        $response = $this->post(route('register'), [
            'name'                  => 'Brand New User',
            'username'              => 'brandnewuser',
            'email'                 => 'new@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('onboarding.show'));
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function createVictorFor(User $user, string $displayName): VictoryGamesVictor
    {
        return VictoryGamesVictor::create([
            'user_id'      => $user->id,
            'slug'         => VictoryGamesVictor::generateSlug($displayName),
            'display_name' => $displayName,
            'email'        => $user->email,
            'claim_token'  => VictoryGamesVictor::generateClaimToken(),
        ]);
    }

    private function createAppFor(VictoryGamesVictor $victor, string $name): VictoryGamesApp
    {
        $app = VictoryGamesApp::create([
            'name'        => $name,
            'slug'        => VictoryGamesApp::generateSlug($name),
            'current_url' => 'https://example.com',
        ]);

        $app->victors()->attach($victor->id, ['role' => 'owner']);

        return $app;
    }
}
