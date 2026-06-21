<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\CurrencyExchangeRate;
use App\Repositories\ExchangeRateRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class ExchangeRateController extends Controller
{
    protected ExchangeRateRepository $repository;

    public function __construct(ExchangeRateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $fromCode = $request->input('from_currency_code');
        $toCode = $request->input('to_currency_code');
        $date = $request->input('date');

        try {
            $rates = $this->repository->getAllRates($fromCode, $toCode, $date);
        } catch (\Exception $e) {
            $rates = collect([]);
        }

        return response()->json([
            'data' => $rates,
        ]);
    }

    public function active(Request $request)
    {
        $date = $request->input('date');

        try {
            $rates = $this->repository->getActiveRates($date);
        } catch (\Exception $e) {
            $rates = collect([]);
        }

        return response()->json([
            'data' => $rates,
        ]);
    }

    public function show($id)
    {
        $rate = CurrencyExchangeRate::with(['fromCurrency', 'toCurrency'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $rate,
        ]);
    }

    public function getRate(Request $request)
    {
        $validated = $request->validate([
            'from_currency_code' => 'required|string',
            'to_currency_code' => 'required|string',
            'date' => 'nullable|date',
        ]);

        try {
            $rate = $this->repository->getLatestRate(
                $validated['from_currency_code'],
                $validated['to_currency_code'],
                $validated['date'] ?? null
            );
        } catch (\Exception $e) {
            $rate = null;
        }

        if (!$rate) {
            return response()->json([
                'success' => false,
                'message' => 'Exchange rate not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'from_currency_code' => $rate->from_currency_code,
                'to_currency_code' => $rate->to_currency_code,
                'rate' => $rate->rate,
                'effective_date' => $rate->effective_date,
            ],
        ]);
    }

    public function convert(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'from_currency_code' => 'required|string',
            'to_currency_code' => 'required|string',
            'date' => 'nullable|date',
        ]);

        try {
            $result = $this->repository->convertWithDetail(
                (float) $validated['amount'],
                $validated['from_currency_code'],
                $validated['to_currency_code'],
                $validated['date'] ?? null
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Conversion failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json($result);
    }

    public function matrix(Request $request)
    {
        $validated = $request->validate([
            'currency_codes' => 'required|array',
            'currency_codes.*' => 'string',
            'date' => 'nullable|date',
        ]);

        try {
            $matrix = $this->repository->getExchangeRateMatrix(
                $validated['currency_codes'],
                $validated['date'] ?? null
            );
        } catch (\Exception $e) {
            $matrix = [];
        }

        return response()->json([
            'success' => true,
            'data' => $matrix,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_currency_code' => 'required|string|exists:currencies,code',
            'to_currency_code' => 'required|string|exists:currencies,code|different:from_currency_code',
            'rate' => 'required|numeric|gt:0',
            'effective_date' => 'nullable|date',
            'source' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $rate = $this->repository->createRate($validated);
            $rate->load(['fromCurrency', 'toCurrency']);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $rate,
                'message' => 'Exchange rate created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create exchange rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $rate = CurrencyExchangeRate::findOrFail($id);

        $validated = $request->validate([
            'from_currency_code' => 'string|exists:currencies,code',
            'to_currency_code' => 'string|exists:currencies,code|different:from_currency_code',
            'rate' => 'numeric|gt:0',
            'effective_date' => 'nullable|date',
            'source' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $this->repository->updateRate($rate, $validated);
            $rate->refresh();
            $rate->load(['fromCurrency', 'toCurrency']);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $rate,
                'message' => 'Exchange rate updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update exchange rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $rate = CurrencyExchangeRate::findOrFail($id);

        DB::beginTransaction();
        try {
            $this->repository->deleteRate($rate);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Exchange rate deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete exchange rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function activate($id)
    {
        $rate = CurrencyExchangeRate::findOrFail($id);

        DB::beginTransaction();
        try {
            $this->repository->activateRate($rate);
            $rate->refresh();
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $rate,
                'message' => 'Exchange rate activated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate exchange rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deactivate($id)
    {
        $rate = CurrencyExchangeRate::findOrFail($id);

        DB::beginTransaction();
        try {
            $this->repository->deactivateRate($rate);
            $rate->refresh();
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $rate,
                'message' => 'Exchange rate deactivated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate exchange rate: ' . $e->getMessage(),
            ], 500);
        }
    }
}
