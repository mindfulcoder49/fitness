<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('victory_games_run_steps', function (Blueprint $table) {
            $table->mediumText('action_params')->nullable()->change();
            $table->mediumText('intent')->nullable()->change();
            $table->mediumText('reasoning')->nullable()->change();
            $table->mediumText('action_result')->nullable()->change();
            $table->mediumText('error_message')->nullable()->change();
            $table->mediumText('page_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('victory_games_run_steps', function (Blueprint $table) {
            $table->text('action_params')->nullable()->change();
            $table->text('intent')->nullable()->change();
            $table->text('reasoning')->nullable()->change();
            $table->text('action_result')->nullable()->change();
            $table->text('error_message')->nullable()->change();
            $table->text('page_url')->nullable()->change();
        });
    }
};
