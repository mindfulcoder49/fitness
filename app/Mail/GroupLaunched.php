<?php

namespace App\Mail;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GroupLaunched extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Group $group)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your group {$this->group->name} has launched!",
        );
    }

    public function content(): Content
    {
        $this->group->loadMissing('tasks', 'users');

        $currentTask = $this->group->tasks->firstWhere('is_current', true);

        return new Content(
            view: 'emails.group-launched',
            with: [
                'group' => $this->group,
                'currentTask' => $currentTask,
                'memberCount' => $this->group->users->count(),
                'groupUrl' => config('app.url') . '/groups/' . $this->group->id,
                'appName' => config('app.name'),
            ],
        );
    }
}
