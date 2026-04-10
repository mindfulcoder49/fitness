<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('victory_games_run_steps', function (Blueprint $table) {
            $table->text('page_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('victory_games_run_steps', function (Blueprint $table) {
            $table->string('page_url', 2048)->nullable()->change();
        });
    }
};
