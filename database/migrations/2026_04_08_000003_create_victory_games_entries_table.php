<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('victory_games_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('victory_games_competitions')->cascadeOnDelete();
            $table->foreignId('victor_id')->nullable()->constrained('victory_games_victors')->nullOnDelete();
            $table->unsignedInteger('external_entry_id'); // AIUXTester entry integer ID
            $table->string('external_user_id');           // AIUXTester user UUID
            $table->string('app_url')->nullable();
            $table->text('app_goal')->nullable();
            $table->string('app_mode')->nullable();       // desktop|mobile
            $table->string('session_provider')->nullable();
            $table->string('session_model')->nullable();
            $table->string('session_status')->nullable();
            $table->string('session_external_id')->nullable(); // AIUXTester session UUID
            $table->text('submission_note')->nullable();
            $table->unsignedTinyInteger('placement')->nullable(); // 1, 2, 3 or NULL
            $table->json('entry_profile')->nullable();    // {what_it_does, agent_limitations, human_verdict, profile}
            $table->text('postmortem_run_analysis')->nullable();
            $table->text('postmortem_html_analysis')->nullable();
            $table->text('postmortem_recommendations')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'external_entry_id']);
            $table->index(['competition_id', 'placement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('victory_games_entries');
    }
};
