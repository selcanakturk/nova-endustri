<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\QuoteController as AdminQuoteController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/hakkimizda', 'hakkimizda')->name('about');
Route::get('/urunler', [ProductController::class, 'index'])->name('products');
Route::get('/urunler/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::view('/hizmetler', 'hizmetler', ['services' => config('nova.services')])->name('services');
Route::view('/iletisim', 'iletisim')->name('contact');
Route::post('/iletisim', [ContactController::class, 'store'])->middleware('throttle:inquiries')->name('contact.store');
Route::view('/aydinlatma', 'privacy')->name('privacy');
Route::get('/urunler/{slug}/teklif', [QuoteController::class, 'create'])->name('quotes.create');
Route::post('/urunler/{slug}/teklif', [QuoteController::class, 'store'])->middleware('throttle:inquiries')->name('quotes.store');

Route::get('/blog', [PostController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [PostController::class, 'show'])->name('blog.show');
Route::get('/haberler', [PostController::class, 'index'])->name('news.index');
Route::get('/haberler/{slug}', [PostController::class, 'show'])->name('news.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [SessionController::class, 'create'])->name('login');
        Route::post('/login', [SessionController::class, 'store'])->name('login.store');
    });

    Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::post('/posts/preview', [AdminPostController::class, 'preview'])->name('posts.preview');
        Route::get('/posts/{post}/delete', [AdminPostController::class, 'confirmDelete'])->name('posts.delete');
        Route::resource('posts', AdminPostController::class)->except('show');
        Route::get('/post-categories/{post_category}/delete', [PostCategoryController::class, 'confirmDelete'])->name('post-categories.delete');
        Route::resource('post-categories', PostCategoryController::class)->except('show');
        Route::get('/messages/{message}/delete', [MessageController::class, 'confirmDelete'])->name('messages.delete');
        Route::resource('messages', MessageController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::get('/quotes/{quote}/delete', [AdminQuoteController::class, 'confirmDelete'])->name('quotes.delete');
        Route::resource('quotes', AdminQuoteController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/categories/{category}/delete', [AdminCategoryController::class, 'confirmDelete'])->name('categories.delete');
        Route::resource('categories', AdminCategoryController::class)->except('show');
        Route::get('/products/{product}/delete', [AdminProductController::class, 'confirmDelete'])->name('products.delete');
        Route::resource('products', AdminProductController::class)->except('show');
    });
});
