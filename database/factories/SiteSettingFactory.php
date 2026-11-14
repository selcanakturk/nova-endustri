<?php

namespace Database\Factories;

use App\CmsContent;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiteSetting> */
class SiteSettingFactory extends Factory
{
    public function definition(): array
    {
        return ['singleton_key' => 'site', 'data' => CmsContent::settingDefaults()];
    }
}
