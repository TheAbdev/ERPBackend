<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\Permission;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     * Returns only permissions that the current user has access to.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Load tenant relationship if not already loaded
        if (!$user->relationLoaded('tenant')) {
            $user->load('tenant');
        }

        // Check if user is Tenant Owner
        $isTenantOwner = $user->isTenantOwner();
        
        // Tenant Owner can view all permissions
        // Otherwise, check if user has permission to view permissions
        if (!$isTenantOwner && !$user->hasPermission('core.permissions.viewAny')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Get all permissions that the user has (through their roles)
        // This includes Tenant Owner - they will only see permissions assigned to their super_admin role
        $userPermissions = $user->getPermissions();
        $userPermissionIds = $userPermissions->pluck('id')->toArray();

        // Filter to only show permissions the user has (applies to all users including Tenant Owner)
        $query = Permission::query();
        $query->whereIn('id', $userPermissionIds);
        
        // Exclude platform-level permissions that should not be available to Tenant Owner
        // These permissions are only for Site Owner (platform.manage permission)
        $query->where('slug', '!=', 'platform.manage')
              ->where('slug', 'not like', 'core.tenants.%')
              ->where('slug', 'not like', 'core.audit_logs.%');

        // Filter by module if provided
        if ($request->has('module')) {
            $query->where('module', $request->input('module'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Group by module for better organization
        $permissions = $query->orderBy('module')->orderBy('name')->get();

        // Transform permissions to array format
        $permissionsArray = $permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
                'module' => $permission->module,
                'description' => $permission->description,
            ];
        })->values()->toArray();

        // Group permissions by module
        $grouped = $permissions->groupBy('module')->map(function ($group) {
            return $group->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'slug' => $permission->slug,
                    'module' => $permission->module,
                    'description' => $permission->description,
                ];
            })->values();
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => $permissionsArray,
            'grouped' => $grouped,
        ]);
    }
}

