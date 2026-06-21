<?php

namespace App\Http\Controllers;

use App\Services\ExchangeRateService;
use App\Services\PermissionService;
use App\Http\Requests\StoreExchangeRateRequest;
use App\Http\Requests\UpdateExchangeRateRequest;
use App\Http\Requests\GetExchangeRateRequest;
use App\Http\Requests\ConvertCurrencyRequest;
use App\Http\Requests\ExchangeRateMatrixRequest;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    protected ExchangeRateService $exchangeRateService;
    protected PermissionService $permissionService;

    public function __construct(
        ExchangeRateService $exchangeRateService,
        PermissionService $permissionService
    ) {
        $this->exchangeRateService = $exchangeRateService;
        $this->permissionService = $permissionService;
    }

    public function index(Request $request)
    {
        $this->permissionService->requirePermission('exchange_rate.view');

        $fromCode = $request->input('from_currency_code');
        $toCode = $request->input('to_currency_code');
        $date = $request->input('date');

        $rates = $this->exchangeRateService->getAll($fromCode, $toCode, $date);

        return response()->json([
            'data' => $rates,
        ]);
    }

    public function active(Request $request)
    {
        $this->permissionService->requirePermission('exchange_rate.view');

        $date = $request->input('date');
        $rates = $this->exchangeRateService->getActive($date);

        return response()->json([
            'data' => $rates,
        ]);
    }

    public function show($id)
    {
        $this->permissionService->requirePermission('exchange_rate.view');

        $rate = $this->exchangeRateService->getById((int) $id);

        return response()->json([
            'success' => true,
            'data' => $rate,
        ]);
    }

    public function getRate(GetExchangeRateRequest $request)
    {
        $this->permissionService->requirePermission('exchange_rate.view');

        $validated = $request->validated();
        $rate = $this->exchangeRateService->getLatest(
            $validated['from_currency_code'],
            $validated['to_currency_code'],
            $validated['date'] ?? null
        );

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

    public function convert(ConvertCurrencyRequest $request)
    {
        $this->permissionService->requirePermission('exchange_rate.convert');

        $validated = $request->validated();
        $result = $this->exchangeRateService->convertWithDetail(
            (float) $validated['amount'],
            $validated['from_currency_code'],
            $validated['to_currency_code'],
            $validated['date'] ?? null
        );

        return response()->json($result);
    }

    public function matrix(ExchangeRateMatrixRequest $request)
    {
        $this->permissionService->requirePermission('exchange_rate.view');

        $validated = $request->validated();
        $matrix = $this->exchangeRateService->getMatrix(
            $validated['currency_codes'],
            $validated['date'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $matrix,
        ]);
    }

    public function store(StoreExchangeRateRequest $request)
    {
        $this->permissionService->requirePermission('exchange_rate.create');

        $rate = $this->exchangeRateService->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $rate,
            'message' => 'Exchange rate created successfully',
        ], 201);
    }

    public function update(UpdateExchangeRateRequest $request, $id)
    {
        $this->permissionService->requirePermission('exchange_rate.update');

        $rate = $this->exchangeRateService->update((int) $id, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $rate,
            'message' => 'Exchange rate updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->permissionService->requirePermission('exchange_rate.delete');

        $this->exchangeRateService->delete((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate deleted successfully',
        ]);
    }

    public function activate($id)
    {
        $this->permissionService->requirePermission('exchange_rate.activate');

        $rate = $this->exchangeRateService->activate((int) $id);

        return response()->json([
            'success' => true,
            'data' => $rate,
            'message' => 'Exchange rate activated successfully',
        ]);
    }

    public function deactivate($id)
    {
        $this->permissionService->requirePermission('exchange_rate.deactivate');

        $rate = $this->exchangeRateService->deactivate((int) $id);

        return response()->json([
            'success' => true,
            'data' => $rate,
            'message' => 'Exchange rate deactivated successfully',
        ]);
    }
}
