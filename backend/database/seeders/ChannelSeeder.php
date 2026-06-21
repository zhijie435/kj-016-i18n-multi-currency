<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Locale;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        $zhCN = Locale::findByCode('zh_CN');
        $en = Locale::findByCode('en');
        $ptBR = Locale::findByCode('pt_BR');
        $ru = Locale::findByCode('ru');

        $channels = [
            [
                'code' => 'cn_main',
                'name' => '中国主站',
                'description' => '中国大陆地区主站渠道',
                'locale_id' => $zhCN->id ?? null,
                'currency_code' => 'CNY',
                'currency_symbol' => '¥',
                'currency_decimals' => 2,
                'is_enabled' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'us_main',
                'name' => 'US Main',
                'description' => 'United States main channel',
                'locale_id' => $en->id ?? null,
                'currency_code' => 'USD',
                'currency_symbol' => '$',
                'currency_decimals' => 2,
                'is_enabled' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'eu_main',
                'name' => 'EU Main',
                'description' => 'European Union main channel',
                'locale_id' => $en->id ?? null,
                'currency_code' => 'EUR',
                'currency_symbol' => '€',
                'currency_decimals' => 2,
                'is_enabled' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'br_main',
                'name' => 'Brasil Principal',
                'description' => 'Canal principal do Brasil',
                'locale_id' => $ptBR->id ?? null,
                'currency_code' => 'BRL',
                'currency_symbol' => 'R$',
                'currency_decimals' => 2,
                'is_enabled' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'ru_main',
                'name' => 'Россия Основной',
                'description' => 'Основной канал России',
                'locale_id' => $ru->id ?? null,
                'currency_code' => 'RUB',
                'currency_symbol' => '₽',
                'currency_decimals' => 2,
                'is_enabled' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($channels as $channel) {
            DB::table('channels')->updateOrInsert(
                ['code' => $channel['code']],
                $channel
            );
        }
    }
}
