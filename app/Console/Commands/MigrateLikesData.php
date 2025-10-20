<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Post;

class MigrateLikesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:migrate-likes {--export} {--import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export and import likes for migration to polymorphic structure.';

    protected string $filePath;

    public function __construct()
    {
        parent::__construct();
        $this->filePath = storage_path('app/likes_migration.json');
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
        $this->info('Exporting likes data...');

        if (!DB::getSchemaBuilder()->hasTable('likes')) {
            $this->error('The likes table does not exist. Nothing to export.');
            return;
        }

        $likes = DB::table('likes')->select('user_id', 'likeable_id', 'likeable_type', 'created_at', 'updated_at')->get();

        File::put($this->filePath, $likes->toJson(JSON_PRETTY_PRINT));

        $this->info('Likes data exported successfully to ' . $this->filePath);
    }

    private function importData(): void
    {
        if (!File::exists($this->filePath)) {
            $this->error('Export file not found. Please run --export first.');
            return;
        }

        $this->info('Importing likes data...');

        $likesData = json_decode(File::get($this->filePath), true);

        try {
            DB::transaction(function () use ($likesData) {
                // Truncate before import to prevent duplicates if run multiple times.
                DB::table('likes')->truncate();

                DB::table('likes')->insert($likesData);
            });

            File::delete($this->filePath);
            $this->info('Import complete and migration file deleted.');

        } catch (\Exception $e) {
            $this->error('An error occurred during import: ' . $e->getMessage());
            $this->error('Import failed. The migration file has been preserved.');
        }
    }
}
