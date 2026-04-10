<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VictoryGamesWelcome;
use App\Models\VictoryGamesCompetition;
use App\Models\VictoryGamesVictor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class VictoryGamesAdminController extends Controller
{
    public function index()
    {
        $competitions = VictoryGamesCompetition::orderByDesc('held_at')->get()
            ->map(function ($comp) {
                $victorIds = VictoryGamesVictor::whereHas(
                    'entries', fn ($q) => $q->where('competition_id', $comp->id)
                )->pluck('id');

                return [
                    'id'           => $comp->id,
                    'slug'         => $comp->slug,
                    'name'         => $comp->name,
                    'held_at'      => $comp->held_at?->toISOString(),
                    'victor_count' => $victorIds->count(),
                    'with_email'   => VictoryGamesVictor::whereIn('id', $victorIds)->whereNotNull('email')->count(),
                    'claimed'      => VictoryGamesVictor::whereIn('id', $victorIds)->whereNotNull('user_id')->count(),
                    'emails_sent'  => VictoryGamesVictor::whereIn('id', $victorIds)->whereNotNull('welcome_email_sent_at')->count(),
                ];
            });

        return Inertia::render('Admin/VictoryGames', [
            'competitions' => $competitions,
        ]);
    }

    public function sendWelcomeEmails(VictoryGamesCompetition $competition)
    {
        $victors = VictoryGamesVictor::whereHas(
            'entries', fn ($q) => $q->where('competition_id', $competition->id)
        )->whereNotNull('email')->get();

        $sent   = 0;
        $failed = 0;

        foreach ($victors as $victor) {
            try {
                Mail::to($victor->email)->send(new VictoryGamesWelcome($victor, $competition));
                $victor->update(['welcome_email_sent_at' => now()]);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('VictoryGames welcome email failed', [
                    'victor_id' => $victor->id,
                    'email'     => $victor->email,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        if ($failed > 0) {
            return back()->with('error', "Sent {$sent} welcome emails; {$failed} failed. Check logs for details.");
        }

        return back()->with('success', "Welcome emails sent to {$sent} victors.");
    }
}
