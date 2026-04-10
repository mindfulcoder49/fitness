<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('victory_games_victors', function (Blueprint $table) {
            $table->string('email')->nullable()->after('external_user_id');
            $table->timestamp('welcome_email_sent_at')->nullable()->after('twitter_url');
        });
    }

    public function down(): void
    {
        Schema::table('victory_games_victors', function (Blueprint $table) {
            $table->dropColumn(['email', 'welcome_email_sent_at']);
        });
    }
};
