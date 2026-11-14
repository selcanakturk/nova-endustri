<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('cms.pages') as $key => $definition) {
            $entry = Page::firstOrNew(['key' => $key]);
            if (! $entry->exists) {
                $entry->fill(['title' => $definition['title'], 'content' => collect($definition['fields'])->map(fn (array $field) => $field['default'])->all(), 'seo' => ['meta_title' => $definition['meta_title'], 'meta_description' => $definition['meta_description']]]);
                $entry->save();
            }
        }
    }
}
