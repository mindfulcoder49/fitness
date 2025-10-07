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
        Schema::table('likes', function (Blueprint $table) {
            // Drop the old foreign key
            $table->dropForeign(['post_id']);
            // Rename post_id to likeable_id
            $table->renameColumn('post_id', 'likeable_id');
            // Add the likeable_type column
            $table->string('likeable_type')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->dropColumn('likeable_type');
            $table->renameColumn('likeable_id', 'post_id');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
        });
    }
};
