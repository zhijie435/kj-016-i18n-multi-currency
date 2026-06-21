<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChannelController extends Controller
{
    public function index()
    {
        $channels = Channel::with('locale')->ordered()->get();

        return response()->json([
            'data' => $channels,
        ]);
    }

    public function enabled()
    {
        $channels = Channel::with('locale')->enabled()->ordered()->get();

        return response()->json([
            'data' => $channels,
        ]);
    }

    public function show($id)
    {
        $channel = Channel::with('locale')->findOrFail($id);

        return response()->json([
            'data' => $channel,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:64|unique:channels,code',
            'name' => 'required|string|max:128',
            'description' => 'nullable|string',
            'locale_code' => 'nullable|string|exists:locales,code',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $channel = new Channel();
            $channel->code = $validated['code'];
            $channel->name = $validated['name'];
            $channel->description = $validated['description'] ?? null;
            $channel->is_enabled = $validated['is_enabled'] ?? true;
            $channel->sort_order = $validated['sort_order'] ?? 0;

            if (isset($validated['locale_code'])) {
                $locale = Locale::findByCode($validated['locale_code']);
                if ($locale) {
                    $channel->locale()->associate($locale);
                }
            }

            $channel->save();
            $channel->load('locale');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $channel,
                'message' => 'Channel created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create channel: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $channel = Channel::findOrFail($id);

        $validated = $request->validate([
            'code' => 'string|max:64|unique:channels,code,' . $channel->id,
            'name' => 'string|max:128',
            'description' => 'nullable|string',
            'locale_code' => 'nullable|string',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['code'])) {
                $channel->code = $validated['code'];
            }
            if (isset($validated['name'])) {
                $channel->name = $validated['name'];
            }
            if (array_key_exists('description', $validated)) {
                $channel->description = $validated['description'];
            }
            if (isset($validated['is_enabled'])) {
                $channel->is_enabled = $validated['is_enabled'];
            }
            if (isset($validated['sort_order'])) {
                $channel->sort_order = $validated['sort_order'];
            }

            if (array_key_exists('locale_code', $validated)) {
                if ($validated['locale_code'] === null || $validated['locale_code'] === '') {
                    $channel->locale()->dissociate();
                } else {
                    $locale = Locale::findByCode($validated['locale_code']);
                    if ($locale) {
                        $channel->locale()->associate($locale);
                    }
                }
            }

            $channel->save();
            $channel->load('locale');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $channel,
                'message' => 'Channel updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update channel: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateLocale(Request $request, $id)
    {
        $channel = Channel::findOrFail($id);

        $validated = $request->validate([
            'locale_code' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $locale = Locale::findByCode($validated['locale_code']);
            if (!$locale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Locale not found',
                ], 404);
            }

            $channel->locale()->associate($locale);
            $channel->save();
            $channel->load('locale');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $channel,
                'message' => 'Channel locale updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update channel locale: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $channel = Channel::findOrFail($id);

        DB::beginTransaction();
        try {
            $channel->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Channel deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete channel: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getChannelLocale($channelCode)
    {
        $localeCode = Channel::getChannelLocaleCode($channelCode);

        if (!$localeCode) {
            return response()->json([
                'success' => false,
                'message' => 'Channel or locale not found',
            ], 404);
        }

        $locale = Locale::findByCode($localeCode);

        return response()->json([
            'success' => true,
            'data' => $locale,
        ]);
    }
}
