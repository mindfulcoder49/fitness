<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('victory_games_entry_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('victory_games_entries')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_number')->nullable();
            $table->string('level', 16);
            $table->string('message');
            $table->mediumText('details')->nullable();
            $table->timestamps();

            $table->index(['entry_id', 'id']);
        });

        Schema::create('victory_games_entry_html_captures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('victory_games_entries')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_number');
            $table->string('url', 2048)->nullable();
            $table->mediumText('html');
            $table->timestamps();

            $table->index(['entry_id', 'step_number']);
        });

        Schema::create('victory_games_entry_memory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('victory_games_entries')->cascadeOnDelete();
            $table->string('memory_key');
            $table->mediumText('memory_value');
            $table->timestamps();

            $table->unique(['entry_id', 'memory_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('victory_games_entry_memory');
        Schema::dropIfExists('victory_games_entry_html_captures');
        Schema::dropIfExists('victory_games_entry_logs');
    }
};
