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
        // New table for groups
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        // Pivot table for group membership
        Schema::create('group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('member'); // e.g., member, admin, leader
            $table->string('location')->nullable();
            $table->integer('points')->default(0);
            $table->timestamps();
            $table->unique(['group_id', 'user_id']);
        });

        // New table for group-specific tasks (replaces daily_fitness_goal)
        Schema::create('group_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        // New table for meetups
        Schema::create('meetups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location');
            $table->dateTime('scheduled_at');
            $table->timestamps();
        });

        // New table for user availability for meetups
        Schema::create('user_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->json('availability'); // e.g., {'weekday_evenings': true, 'weekends': false}
            $table->timestamps();
            $table->unique(['user_id', 'group_id']);
        });

        // Add group_id to posts table
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
        });

        // Remove columns from users table that are now handled by group_user
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'daily_fitness_goal',
                'invitation_sent_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('prospective');
            $table->text('daily_fitness_goal')->nullable();
            $table->timestamp('invitation_sent_at')->nullable();
        });

        Schema::dropIfExists('user_availabilities');
        Schema::dropIfExists('meetups');
        Schema::dropIfExists('group_tasks');
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('groups');
    }
};
