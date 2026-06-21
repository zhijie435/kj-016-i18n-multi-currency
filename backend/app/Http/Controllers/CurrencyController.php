<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Repositories\ExchangeRateRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class CurrencyController extends Controller
{
    protected ExchangeRateRepository $repository;

    public function __construct(ExchangeRateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        try {
            $currencies = $this->repository->getAllCurrencies();
        } catch (\Exception $e) {
            $configCurrencies = Config::get('app.available_currencies', []);
            $currencies = collect($configCurrencies)->map(function ($item, $code) {
                return [
                    'code' => $code,
                    'name' => $item['name'] ?? '',
                    'symbol' => $item['symbol'] ?? '',
                    'decimals' => $item['decimals'] ?? 2,
                    'is_enabled' => true,
                ];
            })->values();
        }

        return response()->json([
            'data' => $currencies,
        ]);
    }

    public function enabled()
    {
        try {
            $currencies = $this->repository->getEnabledCurrencies();
        } catch (\Exception $e) {
            $configCurrencies = Config::get('app.available_currencies', []);
            $currencies = collect($configCurrencies)->map(function ($item, $code) {
                return [
                    'code' => $code,
                    'name' => $item['name'] ?? '',
                    'symbol' => $item['symbol'] ?? '',
                    'decimals' => $item['decimals'] ?? 2,
                    'is_enabled' => true,
                ];
            })->values();
        }

        return response()->json([
            'data' => $currencies,
        ]);
    }

    public function show($code)
    {
        try {
            $currency = $this->repository->getCurrencyByCode($code);
        } catch (\Exception $e) {
            $configCurrencies = Config::get('app.available_currencies', []);
            if (isset($configCurrencies[$code])) {
                $currency = (object) array_merge(['code' => $code], $configCurrencies[$code]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Currency not found',
                ], 404);
            }
        }

        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $currency,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:16|unique:currencies,code',
            'name' => 'required|string|max:64',
            'symbol' => 'nullable|string|max:16',
            'decimals' => 'nullable|integer|min:0|max:8',
            'is_enabled' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $currency = $this->repository->createCurrency($validated);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $currency,
                'message' => 'Currency created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create currency: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $currency = Currency::findOrFail($id);

        $validated = $request->validate([
            'code' => 'string|max:16|unique:currencies,code,' . $currency->id,
            'name' => 'string|max:64',
            'symbol' => 'nullable|string|max:16',
            'decimals' => 'nullable|integer|min:0|max:8',
            'is_enabled' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $this->repository->updateCurrency($currency, $validated);
            $currency->refresh();
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $currency,
                'message' => 'Currency updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update currency: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $currency = Currency::findOrFail($id);

        $defaultCode = Config::get('app.default_currency', 'CNY');
        if ($currency->code === $defaultCode) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the default currency',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $this->repository->deleteCurrency($currency);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Currency deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete currency: ' . $e->getMessage(),
            ], 500);
        }
    }
}
