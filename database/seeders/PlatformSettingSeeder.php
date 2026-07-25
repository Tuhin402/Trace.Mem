<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'allow_admin_registration' => false,
            'founding_offer' => true,
            'maintenance_banner' => false,
            'experimental_features' => false,
        ];

        foreach ($settings as $key => $value) {
            PlatformSetting::setSetting($key, $value);
        }
    }
}
