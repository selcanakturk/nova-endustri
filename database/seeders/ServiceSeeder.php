<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('nova.services') as $index => $data) {
            $entry = Service::firstOrNew(['seed_key' => 'original-'.($index + 1)]);
            if (! $entry->exists) {
                $entry->fill(['title' => $data['title'], 'slug' => Str::slug($data['title'], '-', 'tr'), 'short_description' => $data['description'], 'description' => $data['detail'], 'sort_order' => $index, 'is_active' => true, 'icon' => ['manufacturing', 'assembly', 'engineering'][$index]]);
                $entry->save();
            }
        }
    }
}
