<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChangelogsTable extends Migration
{
    public function up(): void
    {
        Schema::create('changelogs', function (Blueprint $table) {
            $table->id();
            $table->date('release_date');
            $table->json('changes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelogs');
    }
};
