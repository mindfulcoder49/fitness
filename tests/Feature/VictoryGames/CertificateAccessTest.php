<?php

namespace Tests\Feature\VictoryGames;

use App\Models\User;
use App\Models\VictoryGamesCertificate;
use App\Models\VictoryGamesCompetition;
use App\Models\VictoryGamesVictor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests for Victory Games certificate access (index page + tokenized download).
 */
class CertificateAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeVictor(User $user): VictoryGamesVictor
    {
        return VictoryGamesVictor::create([
            'user_id'     => $user->id,
            'slug'        => VictoryGamesVictor::generateSlug($user->name),
            'display_name' => $user->name,
            'email'       => $user->email,
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
        ]);
    }

    private function makeCompetition(): VictoryGamesCompetition
    {
        return VictoryGamesCompetition::create([
            'external_id' => (string) Str::uuid(),
            'slug'        => 'test-competition-'.Str::random(6),
            'name'        => 'Test Competition',
            'status'      => 'complete',
            'held_at'     => now()->subWeek(),
        ]);
    }

    private function makeCertificate(VictoryGamesVictor $victor, VictoryGamesCompetition $competition, string $type = '1st'): VictoryGamesCertificate
    {
        return VictoryGamesCertificate::create([
            'victor_id'        => $victor->id,
            'competition_id'   => $competition->id,
            'certificate_type' => $type,
            'download_token'   => VictoryGamesCertificate::generateToken(),
            'issued_at'        => now(),
        ]);
    }

    // ── Certificate index page ─────────────────────────────────────────────────

    public function test_authenticated_user_with_victor_profile_can_see_certificate_index(): void
    {
        $user        = User::factory()->create();
        $victor      = $this->makeVictor($user);
        $competition = $this->makeCompetition();
        $cert        = $this->makeCertificate($victor, $competition);

        $response = $this->actingAs($user)->get(route('victory-games.certificates'));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('VictoryGames/Certificates')
                 ->where('has_profile', true)
                 ->has('certificates', 1)
        );
    }

    public function test_authenticated_user_without_victor_profile_sees_empty_certificate_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('victory-games.certificates'));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('VictoryGames/Certificates')
                 ->where('has_profile', false)
                 ->has('certificates', 0)
        );
    }

    public function test_guest_is_redirected_from_certificate_index(): void
    {
        $response = $this->get(route('victory-games.certificates'));

        $response->assertRedirect(route('login'));
    }

    // ── Certificates only show the user's own certs, not other users' ─────────

    public function test_certificate_index_only_shows_own_certificates(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $victor  = $this->makeVictor($user);
        $otherV  = $this->makeVictor($other);
        $comp    = $this->makeCompetition();

        $this->makeCertificate($victor, $comp, '1st');
        $this->makeCertificate($otherV, $comp, '2nd');

        $response = $this->actingAs($user)->get(route('victory-games.certificates'));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->has('certificates', 1)
        );
    }

    // ── Tokenized download ────────────────────────────────────────────────────

    public function test_valid_download_token_returns_pdf_response(): void
    {
        $user        = User::factory()->create();
        $victor      = $this->makeVictor($user);
        $competition = $this->makeCompetition();
        $cert        = $this->makeCertificate($victor, $competition, '1st');

        $response = $this->get(route('victory-games.certificates.download', $cert->download_token));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            $response->headers->get('Content-Type')
        );
    }

    public function test_invalid_download_token_returns_404(): void
    {
        $response = $this->get(route('victory-games.certificates.download', 'invalid-token-xyz'));

        $response->assertNotFound();
    }

    public function test_certificate_download_is_accessible_without_login(): void
    {
        $user        = User::factory()->create();
        $victor      = $this->makeVictor($user);
        $competition = $this->makeCompetition();
        $cert        = $this->makeCertificate($victor, $competition, 'participation');

        // Must be accessible without auth (for sharing)
        $response = $this->get(route('victory-games.certificates.download', $cert->download_token));

        $response->assertOk();
    }

    // ── Certificate type labels ───────────────────────────────────────────────

    public function test_certificate_type_labels_are_correct(): void
    {
        $user        = User::factory()->create();
        $victor      = $this->makeVictor($user);
        $competition = $this->makeCompetition();

        $types = [
            '1st'           => '1st Place',
            '2nd'           => '2nd Place',
            '3rd'           => '3rd Place',
            'participation' => 'Participation',
        ];

        foreach ($types as $type => $expectedLabel) {
            $cert = $this->makeCertificate($victor, $competition, $type);
            $this->assertSame($expectedLabel, $cert->typeLabel(), "Failed label check for type '{$type}'");
            $cert->delete();
        }
    }
}
