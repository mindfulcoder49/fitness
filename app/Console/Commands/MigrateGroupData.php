<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\GroupTask;
use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MigrateGroupData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:migrate-groups {--export} {--import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export and import data for migration to multi-group structure.';

    protected string $filePath;

    public function __construct()
    {
        parent::__construct();
        $this->filePath = storage_path('app/data_migration.json');
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('export')) {
            $this->exportData();
        } elseif ($this->option('import')) {
            $this->importData();
        } else {
            $this->error('Please specify either --export or --import flag.');
            return 1;
        }
        return 0;
    }

    private function exportData(): void
    {
        $this->info('Exporting data...');

        $users = User::all(['id', 'role', 'daily_fitness_goal', 'invitation_sent_at']);
        $posts = Post::all(['id']);

        $data = [
            'users' => $users,
            'posts' => $posts,
        ];

        File::put($this->filePath, json_encode($data, JSON_PRETTY_PRINT));

        $this->info('Data exported successfully to ' . $this->filePath);
    }

    private function importData(): void
    {
        if (!File::exists($this->filePath)) {
            $this->error('Export file not found. Please run --export first.');
            return;
        }

        $this->info('Importing data...');

        $data = json_decode(File::get($this->filePath), true);
        $users = $data['users'];
        $posts = $data['posts'];

        DB::transaction(function () use ($users, $posts) {
            // 1. Create a default group
            $firstUser = User::find(1);
            if (!$firstUser) {
                $this->error('Admin user (ID: 1) not found. Cannot create default group.');
                return;
            }

            $group = Group::create([
                'name' => 'Original Fitness Group',
                'description' => 'The first group from the original system.',
                'creator_id' => $firstUser->id,
                'is_public' => true,
            ]);
            $this->info("Created default group: '{$group->name}'");

            // 2. Migrate users to the group
            foreach ($users as $userData) {
                $user = User::find($userData['id']);
                if ($user) {
                    $user->groups()->attach($group->id, [
                        'role' => $userData['role'] ?? 'member',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $this->info('Migrated ' . count($users) . ' users to the default group.');

            // 3. Assign posts to the group
            $postIds = array_column($posts, 'id');
            Post::whereIn('id', $postIds)->update(['group_id' => $group->id]);
            $this->info('Assigned ' . count($postIds) . ' posts to the default group.');

            // 4. Create a default group task from the first user's goal
            $adminUserData = collect($users)->firstWhere('id', 1);
            if ($adminUserData && !empty($adminUserData['daily_fitness_goal'])) {
                GroupTask::create([
                    'group_id' => $group->id,
                    'title' => 'Daily Activity',
                    'description' => $adminUserData['daily_fitness_goal'],
                    'is_current' => true,
                ]);
                $this->info("Created a default group task from admin's old goal.");
            }
        });

        File::delete($this->filePath);
        $this->info('Import complete and migration file deleted.');
    }
}
