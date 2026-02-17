<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_contributor', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contributor_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->unique(['article_id', 'contributor_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_contributor');
    }
};
