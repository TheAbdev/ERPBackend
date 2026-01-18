<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\RoleResource;
use App\Core\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Role::class);

        $query = Role::with(['permissions', 'tenant'])
            ->where('tenant_id', $request->user()->tenant_id);

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by system roles
        if ($request->has('is_system')) {
            $query->where('is_system', $request->boolean('is_system'));
        }

        $roles = $query->latest()->paginate($request->input('per_page', 15));

        return RoleResource::collection($roles);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Ensure uniqueness within tenant
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Role::where('slug', $validated['slug'])
            ->where('tenant_id', $tenantId)
            ->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;

            // Prevent infinite loop (max 1000 attempts)
            if ($counter > 1000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to generate unique slug. Please provide a custom slug.',
                ], 422);
            }
        }

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'tenant_id' => $request->user()->tenant_id,
            'is_system' => false, // Only system can create system roles
        ]);

        // Assign permissions if provided
        if (isset($validated['permission_ids']) && !empty($validated['permission_ids'])) {
            // Validate that user can only assign permissions they have
            $this->validateUserPermissions($request->user(), $validated['permission_ids']);

            $role->permissions()->attach($validated['permission_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => new RoleResource($role->load(['permissions', 'tenant'])),
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        $role->load(['permissions', 'tenant']);

        return response()->json([
            'success' => true,
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        // Prevent updating system roles (except by system)
        if ($role->is_system && !$request->user()->hasPermission('platform.manage')) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be modified.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($role->id)->where(function ($query) use ($request) {
                    return $query->where('tenant_id', $request->user()->tenant_id);
                }),
            ],
            'description' => 'nullable|string|max:1000',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        // Update role data
        if ($request->has('name')) {
            $role->name = $validated['name'];
        }
        if ($request->has('slug') && !empty($validated['slug'])) {
            $role->slug = $validated['slug'];
        }
        if ($request->has('description')) {
            $role->description = $validated['description'];
        }
        $role->save();

        // Update permissions if provided
        if (isset($validated['permission_ids'])) {
            // Validate that user can only assign permissions they have
            $this->validateUserPermissions($request->user(), $validated['permission_ids']);

            $role->permissions()->sync($validated['permission_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => new RoleResource($role->fresh()->load(['permissions', 'tenant'])),
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        // Prevent deleting system roles
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be deleted.',
            ], 403);
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role that is assigned to users.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.',
        ]);
    }

    /**
     * Assign permissions to role.
     */
    public function assignPermissions(Request $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        // Validate that user can only assign permissions they have
        $this->validateUserPermissions($request->user(), $validated['permission_ids']);

        $role->permissions()->syncWithoutDetaching($validated['permission_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Permissions assigned successfully.',
            'data' => new RoleResource($role->fresh()->load(['permissions', 'tenant'])),
        ]);
    }

    /**
     * Remove permissions from role.
     */
    public function removePermissions(Request $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->detach($validated['permission_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Permissions removed successfully.',
            'data' => new RoleResource($role->fresh()->load(['permissions', 'tenant'])),
        ]);
    }

    /**
     * Sync permissions for role (replace all).
     */
    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        // Validate that user can only assign permissions they have
        $this->validateUserPermissions($request->user(), $validated['permission_ids']);

        $role->permissions()->sync($validated['permission_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Permissions synced successfully.',
            'data' => new RoleResource($role->fresh()->load(['permissions', 'tenant'])),
        ]);
    }

    /**
     * Validate that user can only assign permissions they have.
     *
     * @param  \App\Models\User  $user
     * @param  array  $permissionIds
     * @return void
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function validateUserPermissions($user, array $permissionIds): void
    {
        // Tenant Owner can assign any permission
        if ($user->isTenantOwner()) {
            return;
        }

        // Get all permissions that the user has
        $userPermissions = $user->getPermissions();
        $userPermissionIds = $userPermissions->pluck('id')->toArray();

        // Check if all requested permissions are in user's permissions
        $invalidPermissions = array_diff($permissionIds, $userPermissionIds);

        if (!empty($invalidPermissions)) {
            abort(response()->json([
                'success' => false,
                'message' => 'You can only assign permissions that you have access to.',
                'errors' => [
                    'permission_ids' => ['Some selected permissions are not available to you.'],
                ],
            ], 422));
        }
    }
}

