<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('victory_games_apps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('current_url')->nullable();
            $table->timestamps();
        });

        Schema::create('victory_games_app_victor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained('victory_games_apps')->cascadeOnDelete();
            $table->foreignId('victor_id')->constrained('victory_games_victors')->cascadeOnDelete();
            $table->string('role', 16)->default('member'); // owner | member
            $table->timestamps();

            $table->unique(['app_id', 'victor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('victory_games_app_victor');
        Schema::dropIfExists('victory_games_apps');
    }
};
