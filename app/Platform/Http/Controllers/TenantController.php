<?php

namespace App\Platform\Http\Controllers;

use App\Core\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Platform\Http\Requests\AssignTenantOwnerRequest;
use App\Platform\Http\Requests\StoreTenantRequest;
use App\Platform\Http\Requests\UpdateTenantRequest;
use App\Platform\Services\TenantManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    protected TenantManagementService $tenantService;

    public function __construct(TenantManagementService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * List all tenants.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tenant::class);

        $query = Tenant::with(['owner:id,name,email']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search by name or slug
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $tenants = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tenants->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'subdomain' => $tenant->subdomain,
                    'domain' => $tenant->domain,
                    'status' => $tenant->status,
                    'owner' => $tenant->owner ? [
                        'id' => $tenant->owner->id,
                        'name' => $tenant->owner->name,
                        'email' => $tenant->owner->email,
                    ] : null,
                    'usage_stats' => $tenant->getUsageStats(),
                    'created_at' => $tenant->created_at,
                    'updated_at' => $tenant->updated_at,
                ];
            }),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
            ],
        ]);
    }

    /**
     * Create a new tenant.
     *
     * @param  \App\Platform\Http\Requests\StoreTenantRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $this->authorize('create', Tenant::class);

        try {
            $data = $request->validated();

            // Handle owner assignment - support both existing user and new user creation
            if ($request->has('owner_name') && $request->has('owner_email') && $request->has('owner_password')) {
                // Creating new owner user
                $data['owner_name'] = $request->input('owner_name');
                $data['owner_email'] = $request->input('owner_email');
                $data['owner_password'] = $request->input('owner_password');
            } elseif ($request->has('owner_email')) {
                // Using existing user by email
                $data['email'] = $request->input('owner_email');
            } elseif ($request->has('owner_user_id')) {
                // Using existing user by ID
                $data['user_id'] = $request->input('owner_user_id');
            }

            $tenant = $this->tenantService->createTenant($data);

            return response()->json([
                'success' => true,
                'message' => 'Tenant created successfully.',
                'data' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'subdomain' => $tenant->subdomain,
                    'domain' => $tenant->domain,
                    'status' => $tenant->status,
                    'owner' => $tenant->owner ? [
                        'id' => $tenant->owner->id,
                        'name' => $tenant->owner->name,
                        'email' => $tenant->owner->email,
                    ] : null,
                    'created_at' => $tenant->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tenant: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a specific tenant.
     *
     * @param  \App\Core\Models\Tenant  $tenant
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        $tenant->load(['owner:id,name,email']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'subdomain' => $tenant->subdomain,
                'domain' => $tenant->domain,
                'status' => $tenant->status,
                'owner' => $tenant->owner ? [
                    'id' => $tenant->owner->id,
                    'name' => $tenant->owner->name,
                    'email' => $tenant->owner->email,
                ] : null,
                'usage_stats' => $tenant->getUsageStats(),
                'settings' => $tenant->settings,
                'created_at' => $tenant->created_at,
                'updated_at' => $tenant->updated_at,
            ],
        ]);
    }

    /**
     * Update tenant information.
     *
     * @param  \App\Platform\Http\Requests\UpdateTenantRequest  $request
     * @param  \App\Core\Models\Tenant  $tenant
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('update', $tenant);

        try {
            $data = $request->validated();
            $tenant = $this->tenantService->updateTenant($tenant, $data);

            return response()->json([
                'success' => true,
                'message' => 'Tenant updated successfully.',
                'data' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'subdomain' => $tenant->subdomain,
                    'domain' => $tenant->domain,
                    'status' => $tenant->status,
                    'owner' => $tenant->owner ? [
                        'id' => $tenant->owner->id,
                        'name' => $tenant->owner->name,
                        'email' => $tenant->owner->email,
                    ] : null,
                    'updated_at' => $tenant->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tenant: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete tenant (soft delete).
     *
     * @param  \App\Core\Models\Tenant  $tenant
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->authorize('delete', $tenant);

        try {
            $this->tenantService->deleteTenant($tenant);

            return response()->json([
                'success' => true,
                'message' => 'Tenant deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tenant: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign owner to tenant.
     *
     * @param  \App\Platform\Http\Requests\AssignTenantOwnerRequest  $request
     * @param  \App\Core\Models\Tenant  $tenant
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignOwner(AssignTenantOwnerRequest $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('assignOwner', $tenant);

        try {
            $data = $request->validated();

            // Handle owner assignment - support both existing user and new user creation
            if ($request->has('owner_name') && $request->has('owner_email') && $request->has('owner_password')) {
                // Creating new owner user
                $data['owner_name'] = $request->input('owner_name');
                $data['owner_email'] = $request->input('owner_email');
                $data['owner_password'] = $request->input('owner_password');
            } elseif ($request->has('email')) {
                // Using existing user by email
                $data['email'] = $request->input('email');
            } elseif ($request->has('user_id')) {
                // Using existing user by ID
                $data['user_id'] = $request->input('user_id');
            }

            $tenant = $this->tenantService->assignOwner($tenant, $data);

            return response()->json([
                'success' => true,
                'message' => 'Owner assigned successfully.',
                'data' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'owner' => $tenant->owner ? [
                        'id' => $tenant->owner->id,
                        'name' => $tenant->owner->name,
                        'email' => $tenant->owner->email,
                    ] : null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign owner: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate tenant.
     *
     * @param  \App\Core\Models\Tenant  $tenant
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate(Tenant $tenant): JsonResponse
    {
        $this->authorize('activate', $tenant);

        try {
            $tenant = $this->tenantService->activateTenant($tenant);

            return response()->json([
                'success' => true,
                'message' => 'Tenant activated successfully.',
                'data' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'status' => $tenant->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate tenant: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Suspend tenant.
     *
     * @param  \App\Core\Models\Tenant  $tenant
     * @return \Illuminate\Http\JsonResponse
     */
    public function suspend(Tenant $tenant): JsonResponse
    {
        $this->authorize('suspend', $tenant);

        try {
            $tenant = $this->tenantService->suspendTenant($tenant);

            return response()->json([
                'success' => true,
                'message' => 'Tenant suspended successfully.',
                'data' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'status' => $tenant->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend tenant: ' . $e->getMessage(),
            ], 500);
        }
    }
}

