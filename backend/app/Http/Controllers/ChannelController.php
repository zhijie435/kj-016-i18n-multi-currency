<?php

namespace App\Http\Controllers;

use App\Services\ChannelService;
use App\Services\LocaleService;
use App\Services\PermissionService;
use App\Http\Requests\StoreChannelRequest;
use App\Http\Requests\UpdateChannelRequest;
use App\Http\Requests\UpdateChannelLocaleRequest;

class ChannelController extends Controller
{
    protected ChannelService $channelService;
    protected LocaleService $localeService;
    protected PermissionService $permissionService;

    public function __construct(
        ChannelService $channelService,
        LocaleService $localeService,
        PermissionService $permissionService
    ) {
        $this->channelService = $channelService;
        $this->localeService = $localeService;
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $this->permissionService->requirePermission('channel.view');

        $channels = $this->channelService->getAll();

        return response()->json([
            'data' => $channels,
        ]);
    }

    public function enabled()
    {
        $this->permissionService->requirePermission('channel.view');

        $channels = $this->channelService->getEnabled();

        return response()->json([
            'data' => $channels,
        ]);
    }

    public function show($id)
    {
        $this->permissionService->requirePermission('channel.view');

        $channel = $this->channelService->getById((int) $id);

        return response()->json([
            'data' => $channel,
        ]);
    }

    public function store(StoreChannelRequest $request)
    {
        $this->permissionService->requirePermission('channel.create');

        $channel = $this->channelService->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $channel,
            'message' => 'Channel created successfully',
        ], 201);
    }

    public function update(UpdateChannelRequest $request, $id)
    {
        $this->permissionService->requirePermission('channel.update');

        $channel = $this->channelService->update((int) $id, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $channel,
            'message' => 'Channel updated successfully',
        ]);
    }

    public function updateLocale(UpdateChannelLocaleRequest $request, $id)
    {
        $this->permissionService->requirePermission('channel.update');

        $validated = $request->validated();
        $channel = $this->channelService->updateLocale((int) $id, $validated['locale_code']);

        return response()->json([
            'success' => true,
            'data' => $channel,
            'message' => 'Channel locale updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->permissionService->requirePermission('channel.delete');

        $this->channelService->delete((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Channel deleted successfully',
        ]);
    }

    public function getChannelLocale($channelCode)
    {
        $this->permissionService->requirePermission('channel.view');

        $locale = $this->channelService->getChannelLocale($channelCode);

        return response()->json([
            'success' => true,
            'data' => $locale,
        ]);
    }

    public function getChannelCurrency($channelCode)
    {
        $this->permissionService->requirePermission('channel.view');

        $currency = $this->channelService->getChannelCurrency($channelCode);

        return response()->json([
            'success' => true,
            'data' => $currency,
        ]);
    }
}
