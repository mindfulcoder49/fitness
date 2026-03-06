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
        ]);
        $sectionTwo = Section::create([
            'name' => 'Section Two',
            'slug' => 'section-two',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        foreach (range(1, 5) as $index) {
            Article::create([
                'title' => "One {$index}",
                'slug' => "one-{$index}",
                'content' => '<p>x</p>',
                'section_id' => $sectionOne->id,
                'status' => 'published',
                'access' => 'public',
                'published_at' => now()->subMinutes(20 + $index),
            ]);
            Article::create([
                'title' => "Two {$index}",
                'slug' => "two-{$index}",
                'content' => '<p>x</p>',
                'section_id' => $sectionTwo->id,
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
                ->has('sections.0.articles', 4)
                ->has('sections.1.articles', 4)
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
