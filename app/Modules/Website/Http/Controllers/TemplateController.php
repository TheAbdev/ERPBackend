<?php

namespace App\Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Website\Models\WebsiteSite;
use App\Modules\Website\Models\WebsiteTemplate;
use App\Modules\Website\Services\TemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Core\Services\TenantContext;

class TemplateController extends Controller
{
    protected TemplateService $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * List available templates.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantContext = app(TenantContext::class);
        $tenantId = $tenantContext->getTenantId();
        
        // Query all active templates
        // Always show global templates (tenant_id = null) + tenant-specific templates if tenant is resolved
        $query = WebsiteTemplate::where('is_active', true);
        
        if ($tenantId) {
            // Show global templates + tenant-specific templates
            $query->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')
                  ->orWhere('tenant_id', $tenantId);
            });
        } else {
            // No tenant context - show only global templates
            $query->whereNull('tenant_id');
        }
        
        $templates = $query->orderBy('name')->get();

        return response()->json([
            'data' => $templates,
        ]);
    }

    /**
     * Show a specific template.
     */
    public function show(WebsiteTemplate $template): JsonResponse
    {
        return response()->json([
            'data' => $template,
        ]);
    }

    /**
     * Copy a global template to tenant-specific template.
     */
    public function copy(Request $request, WebsiteTemplate $template): JsonResponse
    {
        try {
            $copiedTemplate = $this->templateService->copyTemplate($template->id);

            return response()->json([
                'message' => 'Template copied successfully.',
                'data' => $copiedTemplate,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create a new template.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'config' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $template = $this->templateService->createTemplate($validated);

            return response()->json([
                'message' => 'Template created successfully.',
                'data' => $template,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a template.
     */
    public function update(Request $request, WebsiteTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'config' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $updatedTemplate = $this->templateService->updateTemplate($template->id, $validated);

            return response()->json([
                'message' => 'Template updated successfully.',
                'data' => $updatedTemplate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Apply template to site.
     */
    public function applyToSite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => ['required', 'integer', 'exists:website_templates,id'],
            'site_id' => ['required', 'integer', 'exists:website_sites,id'],
        ]);

        try {
            $site = $this->templateService->applyTemplateToSite(
                $validated['template_id'],
                $validated['site_id']
            );

            return response()->json([
                'message' => 'Template applied to site successfully.',
                'data' => $site,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a template.
     */
    public function destroy(WebsiteTemplate $template): JsonResponse
    {
        try {
            $this->templateService->deleteTemplate($template->id);

            return response()->json([
                'message' => 'Template deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
