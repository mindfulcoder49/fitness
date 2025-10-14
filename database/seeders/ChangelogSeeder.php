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
            ['release_date' => '2025-10-11'],
            [
                'changes' => [
                    '**Improvement: User Stats!** The "Your Stats" panel on the dashboard has been updated to better reflect site activity. It now shows "Likes on Posts" and "Likes Given" instead of comment-focused metrics.',
                    '**Display Fixes: Markdown Rendering!** Fixed several issues with Markdown rendering in posts, including proper handling of line breaks and lists.',
                    '**Longer Posts: Content Length Increased!** The maximum length for post content has been increased from 1000 to many more than that characters to allow for more detailed updates.',
                ],
            ]
        );

        Changelog::updateOrCreate(
            ['release_date' => '2025-10-12'],
            [
                'changes' => [
                    '**Updated Points Calculation!** Likes on blog posts given or received do not count towards points since only the admin can create blog posts. It is still in your to-do list.',
                    '**New Feature: Post Editing!** You can now edit your own posts. Click the three-dot menu on your post and select "Edit" to open the new Markdown editor.',
                    '**New Feature: Featured Posts!** Clicking a post from your notifications or to-do list will now highlight it at the top of your dashboard for easy viewing.',
                    '**Improvement: Mobile Sidebar!** On smaller screens, the sidebar containing the Leaderboard and other panels is now a collapsible menu to save space.',
                    '**Improvement: Collapsible Posts!** Very long posts are now collapsed by default with a "Show more" link to keep the feed tidy.',
                    '**Improvement: Profile Page Feed!** The activity feed on user profile pages now displays full posts, just like on the main dashboard.',
                    '**New:** Blog posts are now distinguished from regular fitness posts in notifications and to-do lists.',
                    '**New:** Added more details to notifications and to-do items, including content previews.',
                    '**Update:** The entire to-do list item is now a clickable link.',
                    '**Fix:** Corrected a timezone bug on the Changelog page that caused dates to display incorrectly.',
                    '**Fix:** Featured posts now correctly display even if they are blog posts.',
                ],
            ]
        );

        Changelog::updateOrCreate(
            ['release_date' => '2025-10-13'],
            [
                'changes' => [
                    '**Major Feature: Multi-Group Platform!** The application has been redesigned to support multiple groups. You can now browse, join, and participate in various public and private communities.',
                    '**New Feature: Group Tasks!** Group admins can now create and manage daily tasks for their members. When posting in a group with an active task, you can link your post to it.',
                    '**New Feature: Group-Specific Stats!** The sidebar on group pages now includes a "Your Stats" panel showing your activity *within that group*.',
                    '**Improvement: Group Page Redesign!** The group page has been updated with a new layout and features.',
                    '**Improvement: Dynamic Group Info Panel!** The sidebar on group pages now dynamically displays the current daily task, if one is set.',
                    '**Improvement: Task Visibility!** Posts that are linked to a task will now display a badge with the task\'s title.',
                    '**Improvement: Responsive Group Page!** The sidebar on group pages is now a collapsible menu on mobile devices for a better viewing experience.',
                ],
            ]
        );

        Changelog::updateOrCreate(
            ['release_date' => '2025-10-14'],
            [
                'changes' => [
                    '**New Feature: Group Chat!** Each group now has its own real-time chat room. Click the "Chat" link in the group header to join the conversation.',
                    '**Update: Welcome Page!** The public-facing welcome page has been completely rewritten to reflect the new multi-group platform.',
                    '**Fix: Daily Post Tracking!** The logic that checks if you\'ve posted \'today\' now correctly uses the Boston timezone (America/New_York) instead of UTC. This ensures daily post limits and to-do items work as expected.',
                    '**Improvement: Link Reliability!** Links to posts from notifications and the to-do list now reliably feature the correct post at the top of the page.',
                    '**New Feature: Group Blog Navigation!** A \'Back to Group\' link has been added to the header of group blog pages for easier navigation.',
                    '**Fix: Create Group Dialog!** Corrected a minor display issue in the create group dialog.',
                    '**Fix: Point Calculations!** Fixed point calculations that regressed during redesign',
                ],
            ]
        );
    }
}
