<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Competition runs (bracket executions)
        Schema::create('victory_games_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('victory_games_competitions')->cascadeOnDelete();
            $table->unsignedInteger('external_run_id');
            $table->unsignedSmallInteger('run_number');
            $table->string('status')->default('complete');
            $table->unsignedInteger('champion_entry_external_id')->nullable();
            $table->string('pairing_strategy')->nullable();
            $table->string('progression_mode')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'external_run_id']);
        });

        // Individual bracket matches within a run
        Schema::create('victory_games_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('victory_games_runs')->cascadeOnDelete();
            $table->unsignedInteger('external_match_id');
            $table->unsignedSmallInteger('round_number');
            $table->unsignedSmallInteger('match_number');
            $table->json('entry_external_ids');          // array of AIUXTester entry integer IDs
            $table->unsignedInteger('winner_entry_external_id')->nullable();
            $table->text('judge_reasoning')->nullable();
            $table->string('status')->default('complete');
            $table->timestamps();

            $table->index(['run_id', 'round_number', 'match_number']);
        });

        // Individual steps within each entry's test run
        Schema::create('victory_games_run_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('victory_games_entries')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_number');
            $table->string('action_type', 64);
            $table->json('action_params')->nullable();
            $table->text('intent')->nullable();
            $table->text('reasoning')->nullable();
            $table->text('action_result')->nullable();
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->string('page_url', 2048)->nullable();
            $table->string('screenshot_path')->nullable(); // storage path for saved PNG
            $table->string('timestamp')->nullable();
            $table->timestamps();

            $table->index(['entry_id', 'step_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('victory_games_run_steps');
        Schema::dropIfExists('victory_games_matches');
        Schema::dropIfExists('victory_games_runs');
    }
};
