<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChangelogUserTable extends Migration
{
    public function up(): void
    {
        Schema::create('changelog_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('changelog_id')->constrained()->onDelete('cascade');
            $table->primary(['user_id', 'changelog_id']);
            $table->timestamp('read_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelog_user');
    }
};
