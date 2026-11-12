<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/hakkimizda', 'hakkimizda')->name('about');
Route::get('/urunler', [ProductController::class, 'index'])->name('products');
Route::get('/urunler/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::view('/hizmetler', 'hizmetler', ['services' => config('nova.services')])->name('services');
Route::view('/iletisim', 'iletisim')->name('contact');
