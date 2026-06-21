<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencyExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $date = now()->toDateString();
        $source = 'seed';

        $baseRates = [
            'CNY' => ['USD' => 0.1389, 'EUR' => 0.1275, 'BRL' => 0.6944, 'RUB' => 13.8889],
            'USD' => ['CNY' => 7.2000, 'EUR' => 0.9180, 'BRL' => 5.0000, 'RUB' => 100.0000],
            'EUR' => ['CNY' => 7.8431, 'USD' => 1.0893, 'BRL' => 5.4466, 'RUB' => 108.9320],
            'BRL' => ['CNY' => 1.4400, 'USD' => 0.2000, 'EUR' => 0.1836, 'RUB' => 20.0000],
            'RUB' => ['CNY' => 0.0720, 'USD' => 0.0100, 'EUR' => 0.0092, 'BRL' => 0.0500],
        ];

        foreach ($baseRates as $fromCode => $toRates) {
            foreach ($toRates as $toCode => $rate) {
                DB::table('currency_exchange_rates')->updateOrInsert(
                    [
                        'from_currency_code' => $fromCode,
                        'to_currency_code' => $toCode,
                        'effective_date' => $date,
                    ],
                    [
                        'from_currency_code' => $fromCode,
                        'to_currency_code' => $toCode,
                        'rate' => $rate,
                        'effective_date' => $date,
                        'source' => $source,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
