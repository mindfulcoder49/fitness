<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrationConsolidationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Running Migration Consolidation Seeder...');

        // List of old migration names that have been consolidated.
        $oldMigrations = [
            '2025_10_05_025211_add_roles_and_fitness_goal_to_users_table',
            '2025_10_06_021500_add_media_permissions_to_users_table',
            '2025_10_06_031500_add_media_to_posts_table',
            '2025_10_06_041500_make_likes_polymorphic',
            '2025_10_06_051500_add_invitation_sent_at_to_users_table',
            '2025_10_07_000000_add_username_to_users_table',
            '2025_10_08_000000_add_is_blog_post_to_posts_table',
            '2025_10_09_000000_add_notifications_last_checked_at_to_users_table',
        ];

        // New consolidated migration names.
        $newMigrations = [
            '2024_05_22_000001_create_cult_tables',
            '2024_05_22_000002_add_cult_columns_to_users_table',
        ];

        // Get migrations that have already been run.
        $ranMigrations = DB::table('migrations')->pluck('migration')->all();

        // Check if any of the old migrations have been run.
        $hasOldMigrations = count(array_intersect($oldMigrations, $ranMigrations)) > 0;

        if ($hasOldMigrations) {
            $this->command->info('Old migrations found. Updating migrations table for consolidated migrations.');

            // Delete old migration entries.
            $deletedCount = DB::table('migrations')->whereIn('migration', $oldMigrations)->delete();
            if ($deletedCount > 0) {
                $this->command->info("Deleted {$deletedCount} old migration entries.");
            }

            // Get the ran migrations again after deletion.
            $ranMigrations = DB::table('migrations')->pluck('migration')->all();

            $batch = DB::table('migrations')->max('batch') ?? 0;

            foreach ($newMigrations as $migration) {
                if (! in_array($migration, $ranMigrations)) {
                    DB::table('migrations')->insert([
                        'migration' => $migration,
                        'batch' => $batch,
                    ]);
                    $this->command->line("Inserted: {$migration}");
                } else {
                    $this->command->line("Skipped (already exists): {$migration}");
                }
            }
            $this->command->info('Migration table updated successfully.');
        } else {
            $this->command->info('No old migrations found to consolidate. No action taken.');
        }
    }
}
