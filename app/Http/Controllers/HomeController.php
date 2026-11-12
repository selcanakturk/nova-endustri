<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', [
            'products' => Product::visible()->featured()->with('category')->ordered()->limit(3)->get(),
            'services' => config('nova.services'),
        ]);
    }
}
