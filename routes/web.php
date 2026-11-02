<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home', ['products' => array_slice(config('nova.products'), 0, 3), 'services' => config('nova.services')])->name('home');
Route::view('/hakkimizda', 'hakkimizda')->name('about');
Route::view('/urunler', 'urunler', ['products' => config('nova.products')])->name('products');
Route::view('/hizmetler', 'hizmetler', ['services' => config('nova.services')])->name('services');
Route::view('/iletisim', 'iletisim')->name('contact');
