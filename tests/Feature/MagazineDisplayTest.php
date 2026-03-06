<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Section;
use App\Models\User;
use App\Models\Vertical;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagazineDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_article_without_publish_date_is_auto_timestamped_on_save(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $section = Section::create([
            'name' => 'News',
            'slug' => 'news',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.magazine.articles.store'), [
            'title' => 'Launch Update',
            'slug' => '',
            'content' => '<p>Hello world</p>',
            'section_id' => $section->id,
            'status' => 'published',
            'access' => 'public',
        ]);

        $article = Article::first();

        $response->assertSessionHasNoErrors();
        $this->assertNotNull($article);
        $this->assertNotNull($article->published_at);
        $this->assertSame('launch-update', $article->slug);
        $this->assertTrue(Article::published()->whereKey($article->id)->exists());
    }

    public function test_vertical_route_returns_404_for_vertical_from_different_section(): void
    {
        $sectionOne = Section::create([
            'name' => 'Culture',
            'slug' => 'culture',
            'is_active' => true,
        ]);
        $sectionTwo = Section::create([
            'name' => 'Tech',
            'slug' => 'tech',
            'is_active' => true,
        ]);
        $vertical = Vertical::create([
            'section_id' => $sectionTwo->id,
            'name' => 'AI Policy',
            'slug' => 'ai-policy',
        ]);

        $this->get(route('magazine.vertical', [$sectionOne->slug, $vertical->slug]))
            ->assertNotFound();
    }

    public function test_article_validation_rejects_vertical_from_another_section(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $sectionOne = Section::create([
            'name' => 'Culture',
            'slug' => 'culture',
            'is_active' => true,
        ]);
        $sectionTwo = Section::create([
            'name' => 'Tech',
            'slug' => 'tech',
            'is_active' => true,
        ]);
        $vertical = Vertical::create([
            'section_id' => $sectionTwo->id,
            'name' => 'AI Policy',
            'slug' => 'ai-policy',
        ]);

        $response = $this->actingAs($admin)->from('/admin/magazine/articles/create')->post(route('admin.magazine.articles.store'), [
            'title' => 'Cross Section Test',
            'content' => '<p>Hello world</p>',
            'section_id' => $sectionOne->id,
            'vertical_id' => $vertical->id,
            'status' => 'draft',
            'access' => 'public',
        ]);

        $response->assertRedirect('/admin/magazine/articles/create');
        $response->assertSessionHasErrors('vertical_id');
        $this->assertDatabaseCount('articles', 0);
    }

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
}
