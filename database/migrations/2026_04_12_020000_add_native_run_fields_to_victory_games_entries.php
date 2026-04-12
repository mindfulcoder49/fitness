<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('victory_games_entries', function (Blueprint $table) {
            $table->foreignId('started_by_user_id')
                ->nullable()
                ->after('victor_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('run_origin')->default('aiuxtester_import')->after('app_id');
            $table->json('run_config')->nullable()->after('session_external_id');
            $table->text('end_reason')->nullable()->after('session_status');
            $table->timestamp('started_at')->nullable()->after('submitted_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
        });

        DB::table('victory_games_entries')
            ->whereNull('run_origin')
            ->update(['run_origin' => 'aiuxtester_import']);
    }

    public function down(): void
    {
        Schema::table('victory_games_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('started_by_user_id');
            $table->dropColumn([
                'run_origin',
                'run_config',
                'end_reason',
                'started_at',
                'completed_at',
            ]);
        });
    }
};
