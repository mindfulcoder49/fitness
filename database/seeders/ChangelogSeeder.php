<?php

namespace Database\Seeders;

use App\Models\Changelog;
use Illuminate\Database\Seeder;

class ChangelogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Changelog::updateOrCreate(
            ['release_date' => '2025-10-10'],
            [
                'changes' => [
                    '**New Feature: Markdown Support!** Posts now support [Markdown](https://www.markdownguide.org/basic-syntax/) formatting for richer content like **bold text**, *italics*, and lists.',
                    '**New Feature: User Profile Pages!** Click on any username on the Leaderboard, posts, or comments to visit their new public profile page. You can see their fitness goal and a feed of their recent activity.',
                    '**New Feature: To-Do List & Notifications!** A To-Do list (checklist icon) and Notifications panel (bell icon) have been added to the dashboard header to help you stay engaged and informed.',
                    '**New Feature: Blog & Changelog Pages!** Blog posts have been moved to a dedicated "Blog" page. This "Changelog" page has also been added to keep you up-to-date with site changes.',
                    '**Improvement: Leaderboard Expanded!** The Leaderboard now displays the top 20 users, up from 10.',
                    '**Improvement: How It Works Panel!** A new panel has been added to the dashboard sidebar to explain how points are earned and how the application process works.',
                    '**Clarification: Points System!** The points system has been clarified: points for posting are awarded for only your first post each day, and points for reading changelog updates have been added',
                ],
            ]
        );

        Changelog::updateOrCreate(
            ['release_date' => '2025-10-17'],
            [
                'changes' => [
                    '**Improvement: User Stats!** The "Your Stats" panel on the dashboard has been updated to better reflect site activity. It now shows "Likes on Posts" and "Likes Given" instead of comment-focused metrics.',
                    '**Display Fixes: Markdown Rendering!** Fixed several issues with Markdown rendering in posts, including proper handling of line breaks and lists.',
                    '**Longer Posts: Content Length Increased!** The maximum length for post content has been increased from 1000 to many more than that characters to allow for more detailed updates.',
                ],
            ]
        );
    }
}
