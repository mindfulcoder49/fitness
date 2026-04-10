<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('victory_games_entries', function (Blueprint $table) {
            // Drop the unique index that references this column before changing its type
            $table->dropUnique(['competition_id', 'external_entry_id']);
        });

        Schema::table('victory_games_entries', function (Blueprint $table) {
            // Widen from unsignedInteger to string so AIUXTester session UUIDs can be stored
            $table->string('external_entry_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('victory_games_entries', function (Blueprint $table) {
            $table->unique(['competition_id', 'external_entry_id']);
            $table->unsignedInteger('external_entry_id')->nullable(false)->change();
        });
    }
};
