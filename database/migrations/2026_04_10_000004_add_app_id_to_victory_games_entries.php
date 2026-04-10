<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('victory_games_entries', function (Blueprint $table) {
            // Allow standalone runs (not tied to a competition)
            $table->foreignId('competition_id')->nullable()->change();

            // Link an entry to an app (competition or standalone)
            $table->foreignId('app_id')
                ->nullable()
                ->after('competition_id')
                ->constrained('victory_games_apps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('victory_games_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('app_id');
            $table->foreignId('competition_id')->nullable(false)->change();
        });
    }
};
