<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\MagazineArticleController;
use App\Http\Controllers\Admin\VictoryGamesAppRunImportController;
use App\Http\Controllers\Admin\VictoryGamesImportController;
use App\Http\Controllers\Admin\VictoryGamesAdminController;
use App\Http\Controllers\VictoryGames\HomeController as VictoryGamesHomeController;
use App\Http\Controllers\VictoryGames\CompetitionController as VictoryGamesCompetitionController;
use App\Http\Controllers\VictoryGames\AppController as VictoryGamesAppController;
use App\Http\Controllers\VictoryGames\VictorController;
use App\Http\Controllers\VictoryGames\RunDetailController;
use App\Http\Controllers\VictoryGames\CertificateController;
use App\Http\Controllers\Admin\MagazineContributorController;
use App\Http\Controllers\Admin\MagazineSectionController;
use App\Http\Controllers\Admin\MagazineTagController;
use App\Http\Controllers\Admin\MagazineVerticalController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMessageController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\Magazine\ArticleController;
use App\Http\Controllers\Magazine\ContributorController;
use App\Http\Controllers\Magazine\HomepageController;
use App\Http\Controllers\Magazine\SectionController;
use App\Http\Controllers\Magazine\TagController;
use App\Http\Controllers\MeetupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserAvailabilityController;
use App\Http\Controllers\UserGoalController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;

// Magazine homepage
Route::get('/', HomepageController::class)->name('magazine.home');

// Community welcome (old homepage)
Route::get('/community', function () {
    return Inertia::render('Community/Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->middleware('groups.enabled')->name('community.welcome');

// Public magazine routes
Route::get('/section/{section:slug}', [SectionController::class, 'show'])->name('magazine.section');
Route::get('/section/{section:slug}/{vertical:slug}', [SectionController::class, 'vertical'])->name('magazine.vertical');
Route::get('/tag/{tag:slug}', [TagController::class, 'show'])->name('magazine.tag');
Route::get('/article/{article:slug}', [ArticleController::class, 'show'])->name('magazine.article');
Route::get('/contributor/{contributor:slug}', [ContributorController::class, 'show'])->name('magazine.contributor');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/blog', [BlogController::class, 'index'])->middleware(['auth', 'verified'])->name('blog.index');
Route::get('/changelog', [ChangelogController::class, 'index'])->middleware(['auth', 'verified'])->name('changelog.index');
Route::get('/users/{user:username}', [UserController::class, 'show'])->middleware(['auth', 'verified'])->name('users.show');

Route::middleware('groups.enabled')->group(function () {
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/{group}/preview', [GroupController::class, 'showPublic'])->name('groups.preview');
    Route::post('/groups', [GroupController::class, 'store'])->middleware(['auth', 'verified'])->name('groups.store');
    Route::post('/groups/{group}/join', [GroupController::class, 'join'])->middleware(['auth', 'verified'])->name('groups.join');
});

Route::middleware(['auth', 'verified', 'group.member', 'groups.enabled'])->group(function () {
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::get('/groups/{group}/blog/{post?}', [GroupController::class, 'blog'])->name('groups.blog');
    Route::get('/groups/{group}/admin', [GroupController::class, 'admin'])->name('groups.admin');
    Route::get('/groups/{group}/chat', [GroupMessageController::class, 'show'])->name('groups.chat');
    Route::get('/groups/{group}/messages', [GroupMessageController::class, 'index'])->name('groups.messages.index');
    Route::post('/groups/{group}/messages', [GroupMessageController::class, 'store'])->name('groups.messages.store');
    Route::get('/groups/{group}/availability', [UserAvailabilityController::class, 'index'])->name('groups.availability.index');
    Route::get('/groups/{group}/availability/data', [UserAvailabilityController::class, 'getData'])->name('groups.availability.data');
    Route::post('/groups/{group}/availability', [UserAvailabilityController::class, 'update'])->name('groups.availability.update');
    Route::get('/groups/{group}/meetups', [MeetupController::class, 'index'])->name('groups.meetups.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    Route::post('/groups/{group}/leave', [GroupController::class, 'leave'])->middleware('groups.enabled')->name('groups.leave');

    Route::post('/likes', [LikeController::class, 'store'])->name('likes.store');
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/changelog/{changelog:id}/read', [ChangelogController::class, 'markAsRead'])->name('changelog.read');
    Route::post('/meetups/{meetup}/rsvp', [MeetupController::class, 'rsvp'])->name('meetups.rsvp');
    Route::get('/todos', [TodoController::class, 'getTodos'])->name('todos.index');
    Route::get('/notifications', [NotificationController::class, 'getNotifications'])->name('notifications.index');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::patch('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
    Route::post('/settings/logo', [AdminController::class, 'updateLogo'])->name('settings.logo');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}/media-permissions', [AdminController::class, 'updateUserMediaPermissions'])->name('users.update-media-permissions');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    Route::delete('/posts/{post}', [AdminController::class, 'destroyPost'])->name('posts.destroy');
    Route::patch('/posts/{post}/toggle-blog', [AdminController::class, 'toggleBlogPost'])->name('posts.toggle-blog');
    Route::delete('/likes/{like}', [AdminController::class, 'destroyLike'])->name('likes.destroy');
    Route::delete('/comments/{comment}', [AdminController::class, 'destroyComment'])->name('comments.destroy');

    Route::middleware('groups.enabled')->group(function () {
        Route::patch('/groups/{group}/members/{user}/role', [AdminController::class, 'updateGroupMemberRole'])->name('groups.members.update-role');
        Route::patch('/groups/{group}/toggle-public', [AdminController::class, 'toggleGroupPublicStatus'])->name('groups.toggle-public');
        Route::patch('/groups/{group}/min-members', [AdminController::class, 'updateGroupMinMembers'])->name('groups.update-min-members');
        Route::post('/groups/{group}/meetups', [MeetupController::class, 'store'])->name('groups.meetups.store');
        Route::patch('/meetups/{meetup}', [MeetupController::class, 'update'])->name('meetups.update');
        Route::delete('/meetups/{meetup}', [MeetupController::class, 'destroy'])->name('meetups.destroy');
        Route::post('/groups/{group}/tasks', [AdminController::class, 'storeTask'])->name('groups.tasks.store');
        Route::patch('/groups/tasks/{task}', [AdminController::class, 'updateTask'])->name('groups.tasks.update');
        Route::delete('/groups/tasks/{task}', [AdminController::class, 'destroyTask'])->name('groups.tasks.destroy');
        Route::patch('/groups/tasks/{task}/set-current', [AdminController::class, 'setCurrentTask'])->name('groups.tasks.set-current');
        Route::patch('/groups/tasks/{task}/unset-current', [AdminController::class, 'unsetCurrentTask'])->name('groups.tasks.unset-current');
        Route::post('/groups/{group}/send-launch-email', [AdminController::class, 'sendGroupEmail'])->name('groups.send-launch-email');
        Route::patch('/groups/{group}/launch', [AdminController::class, 'launchGroup'])->name('groups.launch');
        Route::patch('/groups/{group}/unlaunch', [AdminController::class, 'unlaunchGroup'])->name('groups.unlaunch');
        Route::delete('/groups/{group}', [AdminController::class, 'destroyGroup'])->name('groups.destroy');
    });

    // Victory Games admin routes
    Route::prefix('victory-games')->name('victory-games.')->group(function () {
        Route::get('/', [VictoryGamesAdminController::class, 'index'])->name('index');
        Route::get('/runs', [VictoryGamesAdminController::class, 'runs'])->name('runs.index');
        Route::patch('/runs/{entry}', [VictoryGamesAdminController::class, 'updateRun'])->name('runs.update');
        Route::post('/competitions/{competition}/send-welcome-emails', [VictoryGamesAdminController::class, 'sendWelcomeEmails'])->name('competitions.send-welcome-emails');
        Route::delete('/competitions/{competition}', [VictoryGamesAdminController::class, 'deleteCompetition'])->name('competitions.destroy');
    });

    // Magazine admin routes
    Route::prefix('magazine')->name('magazine.')->group(function () {
        Route::get('/sections', [MagazineSectionController::class, 'index'])->name('sections.index');
        Route::post('/sections', [MagazineSectionController::class, 'store'])->name('sections.store');
        Route::patch('/sections/{section}', [MagazineSectionController::class, 'update'])->name('sections.update');
        Route::delete('/sections/{section}', [MagazineSectionController::class, 'destroy'])->name('sections.destroy');
        Route::post('/sections/reorder', [MagazineSectionController::class, 'reorder'])->name('sections.reorder');

        Route::post('/verticals', [MagazineVerticalController::class, 'store'])->name('verticals.store');
        Route::patch('/verticals/{vertical}', [MagazineVerticalController::class, 'update'])->name('verticals.update');
        Route::delete('/verticals/{vertical}', [MagazineVerticalController::class, 'destroy'])->name('verticals.destroy');

        Route::get('/tags', [MagazineTagController::class, 'index'])->name('tags.index');
        Route::post('/tags', [MagazineTagController::class, 'store'])->name('tags.store');
        Route::patch('/tags/{tag}', [MagazineTagController::class, 'update'])->name('tags.update');
        Route::delete('/tags/{tag}', [MagazineTagController::class, 'destroy'])->name('tags.destroy');

        Route::get('/contributors', [MagazineContributorController::class, 'index'])->name('contributors.index');
        Route::post('/contributors', [MagazineContributorController::class, 'store'])->name('contributors.store');
        Route::patch('/contributors/{contributor}', [MagazineContributorController::class, 'update'])->name('contributors.update');
        Route::delete('/contributors/{contributor}', [MagazineContributorController::class, 'destroy'])->name('contributors.destroy');

        Route::get('/articles', [MagazineArticleController::class, 'index'])->name('articles.index');
        Route::get('/articles/create', [MagazineArticleController::class, 'create'])->name('articles.create');
        Route::post('/articles', [MagazineArticleController::class, 'store'])->name('articles.store');
        Route::get('/articles/{article}/edit', [MagazineArticleController::class, 'edit'])->name('articles.edit');
        Route::patch('/articles/{article}', [MagazineArticleController::class, 'update'])->name('articles.update');
        Route::delete('/articles/{article}', [MagazineArticleController::class, 'destroy'])->name('articles.destroy');
        Route::patch('/articles/{article}/autosave', [MagazineArticleController::class, 'autosave'])->name('articles.autosave');
        Route::post('/articles/{article}/upload-image', [MagazineArticleController::class, 'uploadImage'])->name('articles.upload-image');
        Route::post('/articles/{article}/upload-video', [MagazineArticleController::class, 'uploadVideo'])->name('articles.upload-video');
        Route::post('/articles/{article}/upload-audio', [MagazineArticleController::class, 'uploadAudio'])->name('articles.upload-audio');
    });
});

// ── VibeCode Victory Games ─────────────────────────────────────────────────────
Route::prefix('victory-games')->name('victory-games.')->group(function () {
    // Public pages
    Route::get('/', VictoryGamesHomeController::class)->name('home');
    Route::get('/competitions/{competition:slug}', [VictoryGamesCompetitionController::class, 'show'])->name('competitions.show');
    Route::get('/victors', [VictorController::class, 'index'])->name('victors');
    Route::get('/victors/{victor:slug}', [VictorController::class, 'show'])->name('victors.show');
    Route::get('/runs/{entry}', [RunDetailController::class, 'show'])->name('runs.show');

    // Tokenized cert download — no login required
    Route::get('/certificates/{token}/download', [CertificateController::class, 'download'])->name('certificates.download');

    // Apps — public
    Route::get('/apps/{app:slug}', [VictoryGamesAppController::class, 'show'])->name('apps.show');

    // Authenticated pages
    Route::middleware('auth')->group(function () {
        Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates');
        Route::patch('/victors/{victor:slug}', [VictorController::class, 'update'])->name('victors.update');
        Route::post('/victors/{victor:slug}/claim', [VictorController::class, 'claim'])->name('victors.claim');

        // Apps — auth-gated
        Route::post('/apps', [VictoryGamesAppController::class, 'store'])->name('apps.store');
        Route::patch('/apps/{app:slug}', [VictoryGamesAppController::class, 'update'])->name('apps.update');
        Route::post('/apps/{app:slug}/members', [VictoryGamesAppController::class, 'addMember'])->name('apps.members.add');
        Route::delete('/apps/{app:slug}/members/{victor:slug}', [VictoryGamesAppController::class, 'removeMember'])->name('apps.members.remove');
        Route::post('/runs/{entry}/assign-app', [VictoryGamesAppController::class, 'assignRun'])->name('runs.assign-app');
        Route::delete('/runs/{entry}', [RunDetailController::class, 'destroy'])->name('runs.destroy');
    });
});


// Victory Games import endpoints — Bearer token OR session admin auth, CSRF-exempt
Route::post('/admin/victory-games/import', [VictoryGamesImportController::class, 'store'])->name('admin.victory-games.import');
Route::post('/admin/victory-games/app-run-import', [VictoryGamesAppRunImportController::class, 'store'])->name('admin.victory-games.app-run-import');

require __DIR__.'/auth.php';
