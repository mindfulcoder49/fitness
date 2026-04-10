<?php

namespace Tests\Feature\Magazine;

use App\Models\Article;
use App\Models\Section;
use App\Models\Vertical;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicListingTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_homepage_limits_articles_per_section_not_globally(): void
    {
        $sectionOne = Section::create([
            'name' => 'Section One',
            'slug' => 'section-one',
            'is_active' => true,
            'sort_order' => 1,
            'homepage_article_limit' => 2,
        ]);
        $sectionTwo = Section::create([
            'name' => 'Section Two',
            'slug' => 'section-two',
            'is_active' => true,
            'sort_order' => 2,
            'homepage_article_limit' => 3,
        ]);

        foreach (range(1, 5) as $index) {
            Article::create([
                'title' => "One {$index}",
                'slug' => "one-{$index}",
                'content' => '<p>x</p>',
                'section_id' => $sectionOne->id,
                'section_order' => 6 - $index,
                'status' => 'published',
                'access' => 'public',
                'published_at' => now()->subMinutes(20 + $index),
            ]);
            Article::create([
                'title' => "Two {$index}",
                'slug' => "two-{$index}",
                'content' => '<p>x</p>',
                'section_id' => $sectionTwo->id,
                'section_order' => $index,
                'status' => 'published',
                'access' => 'public',
                'published_at' => now()->subMinutes(40 + $index),
            ]);
        }

        $this->get(route('magazine.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Magazine/Home')
                ->where('sections.0.slug', 'section-one')
                ->where('sections.1.slug', 'section-two')
                ->has('sections.0.articles', 2)
                ->where('sections.0.articles.0.slug', 'one-5')
                ->where('sections.0.articles.1.slug', 'one-4')
                ->has('sections.1.articles', 3)
                ->where('sections.1.articles.0.slug', 'two-1')
                ->where('sections.1.articles.1.slug', 'two-2')
                ->where('sections.1.articles.2.slug', 'two-3')
            );
    }

    public function test_section_page_uses_manual_section_order(): void
    {
        $section = Section::create([
            'name' => 'Ordered',
            'slug' => 'ordered',
            'is_active' => true,
        ]);

        Article::create([
            'title' => 'Second',
            'slug' => 'second',
            'content' => '<p>x</p>',
            'section_id' => $section->id,
            'section_order' => 2,
            'status' => 'published',
            'access' => 'public',
            'published_at' => now()->subDay(),
        ]);

        Article::create([
            'title' => 'First',
            'slug' => 'first',
            'content' => '<p>x</p>',
            'section_id' => $section->id,
            'section_order' => 1,
            'status' => 'published',
            'access' => 'public',
            'published_at' => now()->subDays(2),
        ]);

        $this->get(route('magazine.section', $section->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Magazine/Section')
                ->where('articles.data.0.slug', 'first')
                ->where('articles.data.1.slug', 'second')
            );
    }

    public function test_inactive_section_page_is_not_publicly_accessible(): void
    {
        $section = Section::create([
            'name' => 'Hidden',
            'slug' => 'hidden',
            'is_active' => false,
        ]);

        $this->get(route('magazine.section', $section->slug))
            ->assertNotFound();
    }

    public function test_inactive_section_vertical_page_is_not_publicly_accessible(): void
    {
        $section = Section::create([
            'name' => 'Hidden',
            'slug' => 'hidden',
            'is_active' => false,
        ]);
        $vertical = Vertical::create([
            'section_id' => $section->id,
            'name' => 'Series',
            'slug' => 'series',
        ]);

        $this->get(route('magazine.vertical', [$section->slug, $vertical->slug]))
            ->assertNotFound();
    }
}
