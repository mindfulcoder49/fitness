<?php

namespace Tests\Feature\Magazine;

use App\Models\Article;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_show_404s_when_status_is_published_without_publish_date(): void
    {
        $section = Section::create([
            'name' => 'News',
            'slug' => 'news',
            'is_active' => true,
        ]);
        $article = Article::create([
            'title' => 'Unpublished Edge Case',
            'slug' => 'unpublished-edge-case',
            'content' => '<p>Hello world</p>',
            'section_id' => $section->id,
            'status' => 'published',
            'access' => 'public',
            'published_at' => null,
        ]);

        $this->get(route('magazine.article', $article->slug))
            ->assertNotFound();
    }

    public function test_members_only_article_does_not_leak_full_content_to_guests(): void
    {
        $section = Section::create([
            'name' => 'News',
            'slug' => 'news',
            'is_active' => true,
        ]);

        $article = Article::create([
            'title' => 'Members Only',
            'slug' => 'members-only',
            'excerpt' => 'Public teaser.',
            'content' => '<p>TOP_SECRET_FULL_CONTENT</p>',
            'section_id' => $section->id,
            'status' => 'published',
            'access' => 'members',
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->get(route('magazine.article', $article->slug));

        $response->assertOk();
        $this->assertStringContainsString('Public teaser.', $response->getContent());
        $this->assertStringNotContainsString('TOP_SECRET_FULL_CONTENT', $response->getContent());
    }
}
