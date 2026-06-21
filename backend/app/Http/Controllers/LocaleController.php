<?php

namespace App\Http\Controllers;

use App\Models\Locale;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;

class LocaleController extends Controller
{
    public function index()
    {
        try {
            $availableLocales = Locale::getAvailableLocales();
        } catch (\Exception $e) {
            $availableLocales = Config::get('app.available_locales', []);
        }

        $currentLocale = App::getLocale();
        $availableCurrencies = Config::get('app.available_currencies', []);
        $currentCurrency = Config::get('app.current_currency', $availableCurrencies[Config::get('app.default_currency', 'CNY')] ?? null);

        return response()->json([
            'current' => $currentLocale,
            'available' => $availableLocales,
            'currency' => [
                'current' => $currentCurrency,
                'available' => $availableCurrencies,
            ],
        ]);
    }

    public function show($locale)
    {
        try {
            $availableLocales = Locale::getAvailableLocaleCodes();
        } catch (\Exception $e) {
            $availableLocales = array_keys(Config::get('app.available_locales', []));
        }

        if (!in_array($locale, $availableLocales, true)) {
            return response()->json([
                'error' => 'Unsupported locale',
                'available' => $availableLocales,
            ], 400);
        }

        App::setLocale($locale);

        $messages = [
            'auth'      => Lang::get('auth'),
            'pagination' => Lang::get('pagination'),
            'passwords' => Lang::get('passwords'),
            'validation' => Lang::get('validation'),
            'common'    => Lang::get('common'),
            'menu'      => Lang::get('menu'),
            'packages'  => [
                'content-review' => Lang::get('content-review::messages'),
                'annotation-task' => Lang::get('annotation-task::messages'),
            ],
        ];

        return response()->json([
            'locale'   => $locale,
            'messages' => $messages,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|string',
        ]);

        try {
            $availableLocales = Locale::getAvailableLocaleCodes();
        } catch (\Exception $e) {
            $availableLocales = array_keys(Config::get('app.available_locales', []));
        }

        $locale = $validated['locale'];

        if (!in_array($locale, $availableLocales, true)) {
            return response()->json([
                'error' => 'Unsupported locale',
                'available' => $availableLocales,
            ], 400);
        }

        App::setLocale($locale);
        session()->put('locale', $locale);

        return response()->json([
            'success' => true,
            'locale'  => $locale,
            'message' => 'Locale updated successfully',
        ]);
    }

    public function all()
    {
        $locales = Locale::ordered()->get();

        return response()->json([
            'data' => $locales,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:16|unique:locales,code',
            'name' => 'required|string|max:64',
            'native_name' => 'required|string|max:64',
            'flag' => 'nullable|string|max:16',
            'element_locale' => 'nullable|string|max:32',
            'is_default' => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['is_default']) && $validated['is_default']) {
                Locale::query()->update(['is_default' => false]);
            }

            $locale = Locale::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $locale,
                'message' => 'Locale created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create locale: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateLocale(Request $request, $id)
    {
        $locale = Locale::findOrFail($id);

        $validated = $request->validate([
            'code' => 'string|max:16|unique:locales,code,' . $locale->id,
            'name' => 'string|max:64',
            'native_name' => 'string|max:64',
            'flag' => 'nullable|string|max:16',
            'element_locale' => 'nullable|string|max:32',
            'is_default' => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['is_default']) && $validated['is_default']) {
                Locale::query()->where('id', '!=', $locale->id)->update(['is_default' => false]);
            }

            $locale->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $locale,
                'message' => 'Locale updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update locale: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $locale = Locale::findOrFail($id);

        if ($locale->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the default locale',
            ], 400);
        }

        DB::beginTransaction();
        try {
            Channel::where('locale_id', $locale->id)->update(['locale_id' => null]);
            $locale->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Locale deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete locale: ' . $e->getMessage(),
            ], 500);
        }
    }
}
