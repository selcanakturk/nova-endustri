<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/hakkimizda', 'hakkimizda')->name('about');
Route::get('/urunler', [ProductController::class, 'index'])->name('products');
Route::get('/urunler/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::view('/hizmetler', 'hizmetler', ['services' => config('nova.services')])->name('services');
Route::view('/iletisim', 'iletisim')->name('contact');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [SessionController::class, 'create'])->name('login');
        Route::post('/login', [SessionController::class, 'store'])->name('login.store');
    });

    Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/categories/{category}/delete', [AdminCategoryController::class, 'confirmDelete'])->name('categories.delete');
        Route::resource('categories', AdminCategoryController::class)->except('show');
        Route::get('/products/{product}/delete', [AdminProductController::class, 'confirmDelete'])->name('products.delete');
        Route::resource('products', AdminProductController::class)->except('show');
    });
});
