<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('victory_games_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('victor_id')->constrained('victory_games_victors')->cascadeOnDelete();
            $table->foreignId('competition_id')->constrained('victory_games_competitions')->cascadeOnDelete();
            // 1st | 2nd | 3rd | participation
            $table->string('certificate_type', 20);
            // Secure random token for tokenized download links (no login needed)
            $table->string('download_token', 64)->unique();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->unique(['victor_id', 'competition_id']);
            $table->index('download_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('victory_games_certificates');
    }
};
