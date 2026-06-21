<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $configCurrencies = Config::get('app.available_currencies', []);

        if (empty($configCurrencies)) {
            $configCurrencies = [
                'CNY' => ['name' => '人民币', 'symbol' => '¥', 'code' => 'CNY', 'decimals' => 2],
                'USD' => ['name' => '美元',   'symbol' => '$', 'code' => 'USD', 'decimals' => 2],
                'EUR' => ['name' => '欧元',   'symbol' => '€', 'code' => 'EUR', 'decimals' => 2],
                'BRL' => ['name' => '巴西雷亚尔', 'symbol' => 'R$', 'code' => 'BRL', 'decimals' => 2],
                'RUB' => ['name' => '俄罗斯卢布', 'symbol' => '₽', 'code' => 'RUB', 'decimals' => 2],
            ];
        }

        $sortOrder = 1;
        foreach ($configCurrencies as $code => $currency) {
            DB::table('currencies')->updateOrInsert(
                ['code' => $code],
                [
                    'code' => $code,
                    'name' => $currency['name'] ?? $code,
                    'symbol' => $currency['symbol'] ?? '',
                    'decimals' => $currency['decimals'] ?? 2,
                    'is_enabled' => true,
                    'sort_order' => $sortOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
