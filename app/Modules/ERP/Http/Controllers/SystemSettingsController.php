<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\SystemSettingResource;
use App\Modules\ERP\Models\SystemSetting;
use App\Modules\ERP\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SystemSettingsController extends Controller
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Display a listing of settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SystemSetting::class);

        $query = SystemSetting::where('tenant_id', $request->user()->tenant_id);

        if ($request->has('module')) {
            $query->where('module', $request->input('module'));
        }

        $settings = $query->orderBy('key')->get();

        return SystemSettingResource::collection($settings);
    }

    /**
     * Get setting value.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $key): JsonResponse
    {
        $this->authorize('view', SystemSetting::class);

        $setting = SystemSetting::where('tenant_id', $request->user()->tenant_id)
            ->where('key', $key)
            ->firstOrFail();

        return response()->json([
            'data' => new SystemSettingResource($setting),
        ]);
    }

    /**
     * Store or update a setting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', SystemSetting::class);

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255'],
            'value' => ['required'],
            'module' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', 'in:string,integer,boolean,json'],
            'description' => ['nullable', 'string'],
            'is_encrypted' => ['sometimes', 'boolean'],
        ]);

        $setting = $this->settingsService->set(
            $validated['key'],
            $validated['value'],
            $validated['module'] ?? null,
            $validated['type'] ?? 'string',
            $validated['is_encrypted'] ?? false
        );

        if (isset($validated['description'])) {
            $setting->update(['description' => $validated['description']]);
        }

        return response()->json([
            'message' => 'Setting saved successfully.',
            'data' => new SystemSettingResource($setting),
        ], 201);
    }

    /**
     * Update the specified setting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $this->authorize('update', SystemSetting::class);

        $validated = $request->validate([
            'value' => ['required'],
        ]);

        $setting = $this->settingsService->update($key, $validated['value']);

        return response()->json([
            'message' => 'Setting updated successfully.',
            'data' => new SystemSettingResource($setting),
        ]);
    }

    /**
     * Remove the specified setting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $key): JsonResponse
    {
        $this->authorize('delete', SystemSetting::class);

        $setting = SystemSetting::where('tenant_id', $request->user()->tenant_id)
            ->where('key', $key)
            ->firstOrFail();

        $setting->delete();

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget("setting_{$request->user()->tenant_id}_{$key}");

        return response()->json([
            'message' => 'Setting deleted successfully.',
        ]);
    }
}

