<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Meetup;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAndMeetupAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_read_notifications_for_group_they_do_not_belong_to(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Private Group',
            'is_public' => false,
        ]);
        $group->users()->attach($owner->id, ['role' => 'admin']);

        Post::create([
            'user_id' => $owner->id,
            'group_id' => $group->id,
            'content' => 'Sensitive update',
            'is_blog_post' => false,
        ]);

        $this->actingAs($outsider)
            ->getJson(route('notifications.index', ['group_id' => $group->id]))
            ->assertForbidden();
    }

    public function test_non_member_cannot_rsvp_to_group_meetup(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Private Group',
            'is_public' => false,
        ]);
        $group->users()->attach($owner->id, ['role' => 'admin']);

        $meetup = Meetup::create([
            'group_id' => $group->id,
            'title' => 'Private Meetup',
            'location' => 'Somewhere',
            'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($outsider)
            ->post(route('meetups.rsvp', $meetup), ['status' => 'attending'])
            ->assertForbidden();
    }
}
