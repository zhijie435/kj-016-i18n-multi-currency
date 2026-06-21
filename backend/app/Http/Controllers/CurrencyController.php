<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use App\Services\PermissionService;
use App\Http\Requests\StoreCurrencyRequest;
use App\Http\Requests\UpdateCurrencyRequest;

class CurrencyController extends Controller
{
    protected CurrencyService $currencyService;
    protected PermissionService $permissionService;

    public function __construct(
        CurrencyService $currencyService,
        PermissionService $permissionService
    ) {
        $this->currencyService = $currencyService;
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $this->permissionService->requirePermission('currency.view');

        $currencies = $this->currencyService->getAll();

        return response()->json([
            'data' => $currencies,
        ]);
    }

    public function enabled()
    {
        $this->permissionService->requirePermission('currency.view');

        $currencies = $this->currencyService->getEnabled();

        return response()->json([
            'data' => $currencies,
        ]);
    }

    public function show($code)
    {
        $this->permissionService->requirePermission('currency.view');

        $currency = $this->currencyService->getByCode($code);

        return response()->json([
            'success' => true,
            'data' => $currency,
        ]);
    }

    public function store(StoreCurrencyRequest $request)
    {
        $this->permissionService->requirePermission('currency.create');

        $currency = $this->currencyService->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $currency,
            'message' => 'Currency created successfully',
        ], 201);
    }

    public function update(UpdateCurrencyRequest $request, $id)
    {
        $this->permissionService->requirePermission('currency.update');

        $currency = $this->currencyService->update((int) $id, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $currency,
            'message' => 'Currency updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->permissionService->requirePermission('currency.delete');

        $this->currencyService->delete((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Currency deleted successfully',
        ]);
    }
}
