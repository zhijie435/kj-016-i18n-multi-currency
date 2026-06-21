<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocaleSeeder extends Seeder
{
    public function run(): void
    {
        $locales = [
            [
                'code' => 'zh_CN',
                'name' => '简体中文',
                'native_name' => '简体中文',
                'flag' => '🇨🇳',
                'element_locale' => 'zh-CN',
                'is_default' => true,
                'is_enabled' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'flag' => '🇺🇸',
                'element_locale' => 'en',
                'is_default' => false,
                'is_enabled' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'pt_BR',
                'name' => 'Portuguese',
                'native_name' => 'Português',
                'flag' => '🇧🇷',
                'element_locale' => 'pt-br',
                'is_default' => false,
                'is_enabled' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'ru',
                'name' => 'Russian',
                'native_name' => 'Русский',
                'flag' => '🇷🇺',
                'element_locale' => 'ru-RU',
                'is_default' => false,
                'is_enabled' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($locales as $locale) {
            DB::table('locales')->updateOrInsert(
                ['code' => $locale['code']],
                $locale
            );
        }
    }
}
