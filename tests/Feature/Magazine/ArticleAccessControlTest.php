<?php

namespace Tests\Feature\Magazine;

use App\Models\Article;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for magazine article access control and publication status.
 */
class ArticleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function makeSection(): Section
    {
        return Section::create([
            'name'      => 'Test Section',
            'slug'      => 'test-section',
            'is_active' => true,
        ]);
    }

    private function makeArticle(array $attrs = []): Article
    {
        $section = $this->makeSection();

        return Article::create(array_merge([
            'title'        => 'Test Article',
            'slug'         => 'test-article',
            'content'      => '<p>Full article content.</p>',
            'excerpt'      => 'Short excerpt.',
            'section_id'   => $section->id,
            'status'       => 'published',
            'access'       => 'public',
            'published_at' => now()->subHour(),
        ], $attrs));
    }

    // ── Published public article is readable ──────────────────────────────────

    public function test_published_public_article_is_accessible_to_guests(): void
    {
        $article = $this->makeArticle();

        $response = $this->get(route('magazine.article', $article->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Magazine/Article')
                 ->where('isMembersOnly', false)
        );
    }

    // ── Draft articles return 404 ─────────────────────────────────────────────

    public function test_draft_article_returns_404(): void
    {
        $article = $this->makeArticle(['status' => 'draft']);

        $response = $this->get(route('magazine.article', $article->slug));

        $response->assertNotFound();
    }

    // ── Scheduled (future published_at) articles return 404 ──────────────────

    public function test_scheduled_article_with_future_published_at_returns_404(): void
    {
        $article = $this->makeArticle([
            'status'       => 'published',
            'published_at' => now()->addDays(3),
        ]);

        $response = $this->get(route('magazine.article', $article->slug));

        $response->assertNotFound();
    }

    // ── Archived articles return 404 ─────────────────────────────────────────

    public function test_archived_article_returns_404(): void
    {
        $article = $this->makeArticle(['status' => 'archived']);

        $response = $this->get(route('magazine.article', $article->slug));

        $response->assertNotFound();
    }

    // ── Members-only article shows teaser to guests ───────────────────────────

    public function test_members_only_article_shows_teaser_to_unauthenticated_users(): void
    {
        $article = $this->makeArticle([
            'access'  => 'members',
            'excerpt' => 'This is the teaser excerpt.',
        ]);

        $response = $this->get(route('magazine.article', $article->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Magazine/Article')
                 ->where('isMembersOnly', true)
        );
    }

    // ── Members-only article shows full content to authenticated users ─────────

    public function test_members_only_article_shows_full_content_to_logged_in_users(): void
    {
        $user    = User::factory()->create();
        $article = $this->makeArticle([
            'access'  => 'members',
            'content' => '<p>Full secret content only for members.</p>',
            'excerpt' => 'Just a teaser.',
        ]);

        $response = $this->actingAs($user)->get(route('magazine.article', $article->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Magazine/Article')
                 ->where('isMembersOnly', false)
        );
    }

    // ── Members-only teaser contains excerpt not full content ─────────────────

    public function test_members_only_teaser_uses_excerpt_not_full_content(): void
    {
        $article = $this->makeArticle([
            'access'  => 'members',
            'content' => '<p>Full secret text that should not be in the teaser.</p>',
            'excerpt' => 'Safe teaser text only.',
        ]);

        $response = $this->get(route('magazine.article', $article->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->where('article.content', fn ($content) =>
                str_contains($content, 'Safe teaser text only.') &&
                !str_contains($content, 'Full secret text that should not be in the teaser.')
            )
        );
    }

    // ── Non-existent article slug returns 404 ────────────────────────────────

    public function test_nonexistent_article_slug_returns_404(): void
    {
        $response = $this->get(route('magazine.article', 'no-such-article'));

        $response->assertNotFound();
    }

    // ── Admin can see article admin panel ─────────────────────────────────────

    public function test_admin_can_access_magazine_articles_admin_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.magazine.articles.index'));

        $response->assertOk();
    }

    public function test_non_admin_cannot_access_magazine_articles_admin_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get(route('admin.magazine.articles.index'));

        $response->assertForbidden();
    }

    // ── Admin can create an article ───────────────────────────────────────────

    public function test_admin_can_create_a_new_article(): void
    {
        $admin   = User::factory()->create(['is_admin' => true]);
        $section = $this->makeSection();

        $response = $this->actingAs($admin)->post(route('admin.magazine.articles.store'), [
            'title'      => 'Brand New Article',
            'slug'       => 'brand-new-article',
            'content'    => '<p>Content here.</p>',
            'section_id' => $section->id,
            'status'     => 'draft',
            'access'     => 'public',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('articles', ['slug' => 'brand-new-article']);
    }

    // ── Admin can publish an article ──────────────────────────────────────────

    public function test_admin_can_publish_an_article(): void
    {
        $admin   = User::factory()->create(['is_admin' => true]);
        $article = $this->makeArticle(['status' => 'draft', 'published_at' => null]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.magazine.articles.update', $article), [
                'title'        => $article->title,
                'slug'         => $article->slug,
                'content'      => $article->content,
                'section_id'   => $article->section_id,
                'status'       => 'published',
                'access'       => 'public',
                'published_at' => now()->toDateTimeString(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('published', $article->fresh()->status);
    }
}
