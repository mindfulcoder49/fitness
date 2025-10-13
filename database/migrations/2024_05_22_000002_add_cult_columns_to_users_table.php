<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->after('name')->nullable();
            $table->string('role')->default('prospective')->after('remember_token');
            $table->text('daily_fitness_goal')->nullable()->after('role');
            $table->boolean('can_post_images')->default(false)->after('daily_fitness_goal');
            $table->boolean('can_post_videos')->default(false)->after('can_post_images');
            $table->timestamp('invitation_sent_at')->nullable()->after('can_post_videos');
            $table->timestamp('notifications_last_checked_at')->nullable()->after('invitation_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'role',
                'daily_fitness_goal',
                'can_post_images',
                'can_post_videos',
                'invitation_sent_at',
                'notifications_last_checked_at',
            ]);
        });
    }
};
