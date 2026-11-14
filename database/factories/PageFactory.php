<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Page> */
class PageFactory extends Factory
{
    public function definition(): array
    {
        return ['key' => 'home', 'title' => 'Ana sayfa', 'content' => [], 'seo' => []];
    }
}
