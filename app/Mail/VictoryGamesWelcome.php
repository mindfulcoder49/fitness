<?php

namespace App\Mail;

use App\Models\VictoryGamesCompetition;
use App\Models\VictoryGamesVictor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VictoryGamesWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public VictoryGamesVictor $victor,
        public VictoryGamesCompetition $competition,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your VibeCode Victory Games profile is live!",
        );
    }

    public function content(): Content
    {
        $registerUrl = config('app.url') . '/register?victor_token=' . $this->victor->claim_token;

        return new Content(
            view: 'emails.victory-games-welcome',
            with: [
                'victor'          => $this->victor,
                'competition'     => $this->competition,
                'victorUrl'       => config('app.url') . '/victory-games/victors/' . $this->victor->slug,
                'registerUrl'     => $registerUrl,
                'appName'         => config('app.name'),
                'appsUrl'         => config('app.url') . '/victory-games/victors/' . $this->victor->slug . '#apps',
                'aiuxtesterUrl'   => 'https://aiuxtester.fly.dev',
            ],
        );
    }
}
