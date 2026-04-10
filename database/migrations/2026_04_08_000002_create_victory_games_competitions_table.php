<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('victory_games_competitions', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique(); // AIUXTester competition UUID
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('complete'); // open|closed|complete
            $table->text('overall_narrative')->nullable();  // from AI recap
            $table->string('recap_provider')->nullable();
            $table->string('recap_model')->nullable();
            $table->timestamp('held_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('victory_games_competitions');
    }
};
