<?php

namespace Tests\Feature\Magazine;

use App\Models\Article;
use App\Models\Section;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vertical;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMagazineTest extends TestCase
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

    public function test_article_can_be_updated_with_method_spoofed_form_data_and_featured_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $section = Section::create([
            'name' => 'News',
            'slug' => 'news',
            'is_active' => true,
        ]);

        $article = Article::create([
            'title' => 'Original Title',
            'slug' => 'original-title',
            'content' => '<p>Original body</p>',
            'section_id' => $section->id,
            'status' => 'draft',
            'access' => 'public',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.magazine.articles.edit', $article, false))
            ->post(route('admin.magazine.articles.update', $article), [
                '_method' => 'patch',
                'title' => 'Updated Title',
                'slug' => '',
                'content' => '<p>Updated body</p>',
                'section_id' => $section->id,
                'status' => 'published',
                'access' => 'public',
                'featured_image' => UploadedFile::fake()->image('cover.jpg'),
            ]);

        $response->assertRedirect(route('admin.magazine.articles.edit', $article, false));
        $response->assertSessionHasNoErrors();

        $article = $article->fresh();

        $this->assertSame('Updated Title', $article->title);
        $this->assertSame('<p>Updated body</p>', $article->content);
        $this->assertSame('updated-title', $article->slug);
        $this->assertNotNull($article->published_at);
        $this->assertNotNull($article->featured_image_path);
        Storage::disk('public')->assertExists($article->featured_image_path);
    }

    public function test_admin_can_update_section_homepage_article_limit(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $section = Section::create([
            'name' => 'News',
            'slug' => 'news',
            'homepage_article_limit' => 4,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/magazine/sections')
            ->patch(route('admin.magazine.sections.update', $section), [
                'name' => 'News',
                'slug' => 'news',
                'description' => '',
                'homepage_article_limit' => 2,
                'is_active' => true,
            ]);

        $response->assertRedirect('/admin/magazine/sections');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'homepage_article_limit' => 2,
        ]);
    }

    public function test_admin_can_reorder_articles_within_a_section(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $section = Section::create([
            'name' => 'News',
            'slug' => 'news',
            'is_active' => true,
        ]);

        $first = Article::create([
            'title' => 'First',
            'slug' => 'first',
            'content' => '<p>First</p>',
            'section_id' => $section->id,
            'section_order' => 1,
            'status' => 'published',
            'access' => 'public',
            'published_at' => now()->subDay(),
        ]);

        $second = Article::create([
            'title' => 'Second',
            'slug' => 'second',
            'content' => '<p>Second</p>',
            'section_id' => $section->id,
            'section_order' => 2,
            'status' => 'published',
            'access' => 'public',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.magazine.sections.articles', $section, false))
            ->post(route('admin.magazine.sections.articles.reorder', $section), [
                'order' => [$second->id, $first->id],
            ]);

        $response->assertRedirect(route('admin.magazine.sections.articles', $section, false));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('articles', [
            'id' => $second->id,
            'section_order' => 1,
        ]);
        $this->assertDatabaseHas('articles', [
            'id' => $first->id,
            'section_order' => 2,
        ]);
    }

    public function test_duplicate_section_names_with_auto_slug_do_not_500(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $first = $this->actingAs($admin)->from('/admin/magazine/sections')->post(route('admin.magazine.sections.store'), [
            'name' => 'Data',
            'slug' => '',
            'is_active' => true,
        ]);
        $second = $this->actingAs($admin)->from('/admin/magazine/sections')->post(route('admin.magazine.sections.store'), [
            'name' => 'Data',
            'slug' => '',
            'is_active' => true,
        ]);

        $first->assertRedirect('/admin/magazine/sections');
        $second->assertRedirect('/admin/magazine/sections');
        $this->assertDatabaseHas('sections', ['slug' => 'data']);
        $this->assertDatabaseHas('sections', ['slug' => 'data-2']);
    }

    public function test_duplicate_tag_names_with_auto_slug_do_not_500(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $first = $this->actingAs($admin)->from('/admin/magazine/tags')->post(route('admin.magazine.tags.store'), [
            'name' => 'Policy',
            'slug' => '',
        ]);
        $second = $this->actingAs($admin)->from('/admin/magazine/tags')->post(route('admin.magazine.tags.store'), [
            'name' => 'Policy',
            'slug' => '',
        ]);

        $first->assertRedirect('/admin/magazine/tags');
        $second->assertRedirect('/admin/magazine/tags');
        $this->assertDatabaseHas('tags', ['slug' => 'policy']);
        $this->assertDatabaseHas('tags', ['slug' => 'policy-2']);
    }

    public function test_duplicate_vertical_names_with_auto_slug_do_not_500(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $section = Section::create([
            'name' => 'Data',
            'slug' => 'data',
            'is_active' => true,
        ]);

        $first = $this->actingAs($admin)->from('/admin/magazine/sections')->post(route('admin.magazine.verticals.store'), [
            'section_id' => $section->id,
            'name' => 'Signals',
            'slug' => '',
        ]);
        $second = $this->actingAs($admin)->from('/admin/magazine/sections')->post(route('admin.magazine.verticals.store'), [
            'section_id' => $section->id,
            'name' => 'Signals',
            'slug' => '',
        ]);

        $first->assertRedirect('/admin/magazine/sections');
        $second->assertRedirect('/admin/magazine/sections');
        $this->assertDatabaseHas('verticals', ['slug' => 'signals']);
        $this->assertDatabaseHas('verticals', ['slug' => 'signals-2']);
    }

    public function test_duplicate_contributor_names_with_auto_slug_do_not_500(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $first = $this->actingAs($admin)->from('/admin/magazine/contributors')->post(route('admin.magazine.contributors.store'), [
            'name' => 'Jane Doe',
            'slug' => '',
        ]);
        $second = $this->actingAs($admin)->from('/admin/magazine/contributors')->post(route('admin.magazine.contributors.store'), [
            'name' => 'Jane Doe',
            'slug' => '',
        ]);

        $first->assertRedirect('/admin/magazine/contributors');
        $second->assertRedirect('/admin/magazine/contributors');
        $this->assertDatabaseHas('contributors', ['slug' => 'jane-doe']);
        $this->assertDatabaseHas('contributors', ['slug' => 'jane-doe-2']);
    }

    public function test_user_cannot_be_linked_to_multiple_contributors(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $linkedUser = User::factory()->create();

        $first = $this->actingAs($admin)->from('/admin/magazine/contributors')->post(route('admin.magazine.contributors.store'), [
            'name' => 'One',
            'slug' => '',
            'user_id' => $linkedUser->id,
        ]);
        $second = $this->actingAs($admin)->from('/admin/magazine/contributors')->post(route('admin.magazine.contributors.store'), [
            'name' => 'Two',
            'slug' => '',
            'user_id' => $linkedUser->id,
        ]);

        $first->assertRedirect('/admin/magazine/contributors');
        $second->assertRedirect('/admin/magazine/contributors');
        $second->assertSessionHasErrors('user_id');
    }

    public function test_non_admin_cannot_access_magazine_admin_endpoints(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.magazine.articles.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.magazine.sections.index'))
            ->assertForbidden();
    }
}
