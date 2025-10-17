<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMessageController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGoalController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/blog', [BlogController::class, 'index'])->middleware(['auth', 'verified'])->name('blog.index');
Route::get('/changelog', [ChangelogController::class, 'index'])->middleware(['auth', 'verified'])->name('changelog.index');
Route::get('/users/{user:username}', [UserController::class, 'show'])->middleware(['auth', 'verified'])->name('users.show');

Route::get('/groups', [GroupController::class, 'index'])->middleware(['auth', 'verified'])->name('groups.index');
Route::post('/groups', [GroupController::class, 'store'])->middleware(['auth', 'verified'])->name('groups.store');
Route::post('/groups/{group}/join', [GroupController::class, 'join'])->middleware(['auth', 'verified'])->name('groups.join');

Route::middleware(['auth', 'verified', 'group.member'])->group(function () {
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::get('/groups/{group}/blog/{post?}', [GroupController::class, 'blog'])->name('groups.blog');
    Route::get('/groups/{group}/admin', [GroupController::class, 'admin'])->name('groups.admin');
    Route::get('/groups/{group}/chat', [GroupMessageController::class, 'show'])->name('groups.chat');
    Route::get('/groups/{group}/messages', [GroupMessageController::class, 'index'])->name('groups.messages.index');
    Route::post('/groups/{group}/messages', [GroupMessageController::class, 'store'])->name('groups.messages.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    Route::post('/likes', [LikeController::class, 'store'])->name('likes.store');
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/changelog/{changelog:id}/read', [ChangelogController::class, 'markAsRead'])->name('changelog.read');
    Route::get('/todos', [TodoController::class, 'getTodos'])->name('todos.index');
    Route::get('/notifications', [NotificationController::class, 'getNotifications'])->name('notifications.index');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::patch('/users/{user}/media-permissions', [AdminController::class, 'updateUserMediaPermissions'])->name('users.update-media-permissions');
    Route::patch('/groups/{group}/members/{user}/role', [AdminController::class, 'updateGroupMemberRole'])->name('groups.members.update-role');
    Route::patch('/groups/{group}/toggle-public', [AdminController::class, 'toggleGroupPublicStatus'])->name('groups.toggle-public');
    Route::post('/groups/{group}/tasks', [AdminController::class, 'storeTask'])->name('groups.tasks.store');
    Route::patch('/groups/tasks/{task}', [AdminController::class, 'updateTask'])->name('groups.tasks.update');
    Route::delete('/groups/tasks/{task}', [AdminController::class, 'destroyTask'])->name('groups.tasks.destroy');
    Route::patch('/groups/tasks/{task}/set-current', [AdminController::class, 'setCurrentTask'])->name('groups.tasks.set-current');
    Route::patch('/groups/tasks/{task}/unset-current', [AdminController::class, 'unsetCurrentTask'])->name('groups.tasks.unset-current');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    Route::delete('/posts/{post}', [AdminController::class, 'destroyPost'])->name('posts.destroy');
    Route::patch('/posts/{post}/toggle-blog', [AdminController::class, 'toggleBlogPost'])->name('posts.toggle-blog');
    Route::delete('/likes/{like}', [AdminController::class, 'destroyLike'])->name('likes.destroy');
    Route::delete('/comments/{comment}', [AdminController::class, 'destroyComment'])->name('comments.destroy');
});

require __DIR__.'/auth.php';
