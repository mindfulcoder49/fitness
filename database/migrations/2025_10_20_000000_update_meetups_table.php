<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMeetupsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meetups', function (Blueprint $table) {
            $table->string('type')->nullable()->after('description');
            $table->decimal('latitude', 10, 7)->nullable()->after('location');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        Schema::create('meetup_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meetup_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status'); // e.g., attending, interested
            $table->timestamps();

            $table->unique(['meetup_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetup_user');

        Schema::table('meetups', function (Blueprint $table) {
            $table->dropColumn(['type', 'latitude', 'longitude']);
        });
    }
}
