<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->unsignedInteger('homepage_article_limit')->default(4)->after('sort_order');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedInteger('section_order')->nullable()->after('section_id');
            $table->index(['section_id', 'section_order']);
        });

        $sectionIds = DB::table('sections')->pluck('id');

        foreach ($sectionIds as $sectionId) {
            $articleIds = DB::table('articles')
                ->where('section_id', $sectionId)
                ->orderByRaw('published_at IS NULL')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->pluck('id');

            foreach ($articleIds as $index => $articleId) {
                DB::table('articles')
                    ->where('id', $articleId)
                    ->update(['section_order' => $index + 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['section_id', 'section_order']);
            $table->dropColumn('section_order');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('homepage_article_limit');
        });
    }
};
