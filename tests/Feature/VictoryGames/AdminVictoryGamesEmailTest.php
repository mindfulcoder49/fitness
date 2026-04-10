<?php

namespace Tests\Feature\VictoryGames;

use App\Mail\VictoryGamesWelcome;
use App\Models\User;
use App\Models\VictoryGamesCompetition;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesVictor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminVictoryGamesEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_welcome_emails_to_selected_victors_only(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $competition = VictoryGamesCompetition::create([
            'external_id' => 'comp-1',
            'slug' => 'comp-1',
            'name' => 'Competition One',
            'status' => 'complete',
            'held_at' => now(),
        ]);

        $otherCompetition = VictoryGamesCompetition::create([
            'external_id' => 'comp-2',
            'slug' => 'comp-2',
            'name' => 'Competition Two',
            'status' => 'complete',
            'held_at' => now()->subDay(),
        ]);

        $selectedVictor = VictoryGamesVictor::create([
            'slug' => 'selected-victor',
            'display_name' => 'Selected Victor',
            'email' => 'selected@example.com',
        ]);

        $unselectedVictor = VictoryGamesVictor::create([
            'slug' => 'unselected-victor',
            'display_name' => 'Unselected Victor',
            'email' => 'unselected@example.com',
        ]);

        $otherCompetitionVictor = VictoryGamesVictor::create([
            'slug' => 'other-competition-victor',
            'display_name' => 'Other Competition Victor',
            'email' => 'other@example.com',
        ]);

        $noEmailVictor = VictoryGamesVictor::create([
            'slug' => 'no-email-victor',
            'display_name' => 'No Email Victor',
        ]);

        VictoryGamesEntry::create([
            'competition_id' => $competition->id,
            'victor_id' => $selectedVictor->id,
            'external_entry_id' => 1,
            'external_user_id' => 'selected-user',
        ]);

        VictoryGamesEntry::create([
            'competition_id' => $competition->id,
            'victor_id' => $unselectedVictor->id,
            'external_entry_id' => 2,
            'external_user_id' => 'unselected-user',
        ]);

        VictoryGamesEntry::create([
            'competition_id' => $competition->id,
            'victor_id' => $noEmailVictor->id,
            'external_entry_id' => 3,
            'external_user_id' => 'no-email-user',
        ]);

        VictoryGamesEntry::create([
            'competition_id' => $otherCompetition->id,
            'victor_id' => $otherCompetitionVictor->id,
            'external_entry_id' => 1,
            'external_user_id' => 'other-user',
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/victory-games')
            ->post(route('admin.victory-games.competitions.send-welcome-emails', $competition), [
                'victor_ids' => [$selectedVictor->id],
            ]);

        $response->assertRedirect('/admin/victory-games');
        $response->assertSessionHasNoErrors();

        Mail::assertSent(VictoryGamesWelcome::class, function (VictoryGamesWelcome $mail) use ($selectedVictor, $competition) {
            return $mail->hasTo($selectedVictor->email)
                && $mail->competition->is($competition)
                && $mail->victor->is($selectedVictor);
        });

        Mail::assertNotSent(VictoryGamesWelcome::class, function (VictoryGamesWelcome $mail) use ($unselectedVictor) {
            return $mail->hasTo($unselectedVictor->email);
        });

        Mail::assertNotSent(VictoryGamesWelcome::class, function (VictoryGamesWelcome $mail) use ($otherCompetitionVictor) {
            return $mail->hasTo($otherCompetitionVictor->email);
        });

        $this->assertNotNull($selectedVictor->fresh()->welcome_email_sent_at);
        $this->assertNull($unselectedVictor->fresh()->welcome_email_sent_at);
        $this->assertNull($otherCompetitionVictor->fresh()->welcome_email_sent_at);
        $this->assertNull($noEmailVictor->fresh()->welcome_email_sent_at);
    }
}
