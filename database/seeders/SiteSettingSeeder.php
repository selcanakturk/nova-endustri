<?php

namespace Database\Seeders;

use App\CmsContent;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $entry = SiteSetting::firstOrNew(['singleton_key' => 'site']);
        if (! $entry->exists) {
            $entry->data = CmsContent::settingDefaults();
            $entry->save();
        }
    }
}
