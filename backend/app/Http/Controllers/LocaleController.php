<?php

namespace App\Http\Controllers;

use App\Services\LocaleService;
use App\Services\ChannelService;
use App\Services\CurrencyService;
use App\Http\Requests\StoreLocaleRequest;
use App\Http\Requests\UpdateLocaleRequest;
use App\Http\Requests\UpdateLocalePreferenceRequest;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    protected LocaleService $localeService;
    protected ChannelService $channelService;
    protected CurrencyService $currencyService;
    protected PermissionService $permissionService;

    public function __construct(
        LocaleService $localeService,
        ChannelService $channelService,
        CurrencyService $currencyService,
        PermissionService $permissionService
    ) {
        $this->localeService = $localeService;
        $this->channelService = $channelService;
        $this->currencyService = $currencyService;
        $this->permissionService = $permissionService;
    }

    public function index(Request $request)
    {
        $this->permissionService->requirePermission('locale.view');

        $availableLocales = $this->localeService->getAvailableLocales();
        $currentLocale = App::getLocale();

        $channelCode = $request->header('X-Channel-Code') ?: $request->input('channel_code');
        $context = $this->channelService->getCurrentContext($channelCode);

        return response()->json([
            'current' => $currentLocale,
            'available' => $availableLocales,
            'currency' => [
                'current' => $context['currencies']['current'],
                'available' => $context['currencies']['available'],
            ],
        ]);
    }

    public function show($locale)
    {
        $this->permissionService->requirePermission('locale.view');

        $validLocale = $this->localeService->validateCode($locale);

        App::setLocale($validLocale);

        $messages = [
            'auth'       => Lang::get('auth'),
            'pagination' => Lang::get('pagination'),
            'passwords'  => Lang::get('passwords'),
            'validation' => Lang::get('validation'),
            'common'     => Lang::get('common'),
            'menu'       => Lang::get('menu'),
            'packages'   => [
                'content-review'  => Lang::get('content-review::messages'),
                'annotation-task' => Lang::get('annotation-task::messages'),
            ],
        ];

        return response()->json([
            'locale'   => $validLocale,
            'messages' => $messages,
        ]);
    }

    public function update(UpdateLocalePreferenceRequest $request)
    {
        $this->permissionService->requirePermission('locale.view');

        $validated = $request->validated();
        $validLocale = $this->localeService->validateCode($validated['locale']);

        App::setLocale($validLocale);
        Session::put('locale', $validLocale);

        return response()->json([
            'success' => true,
            'locale'  => $validLocale,
            'message' => 'Locale updated successfully',
        ]);
    }

    public function all()
    {
        $this->permissionService->requirePermission('locale.view');

        $locales = $this->localeService->getAll();

        return response()->json([
            'data' => $locales,
        ]);
    }

    public function store(StoreLocaleRequest $request)
    {
        $this->permissionService->requirePermission('locale.create');

        $locale = $this->localeService->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $locale,
            'message' => 'Locale created successfully',
        ], 201);
    }

    public function updateLocale(UpdateLocaleRequest $request, $id)
    {
        $this->permissionService->requirePermission('locale.update');

        $locale = $this->localeService->update((int) $id, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $locale,
            'message' => 'Locale updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->permissionService->requirePermission('locale.delete');

        $this->localeService->delete((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Locale deleted successfully',
        ]);
    }
}
