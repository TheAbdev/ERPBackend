<?php

namespace App\Platform\Http\Controllers;

use App\Core\Models\Permission;
use App\Core\Models\Role;
use App\Core\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SiteOwnerController extends Controller
{
    /**
     * Create a Site Owner user.
     *
     * This endpoint can be used for initial setup or by existing Site Owner to create additional Site Owners.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        // If user is authenticated, check if they are Site Owner
        if ($request->user()) {
            $isSiteOwner = $request->user()->hasRole('site_owner') ||
                          $request->user()->hasPermission('platform.manage');

            if (! $isSiteOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only existing Site Owners can create new Site Owners.',
                ], 403);
            }
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', new \App\Rules\StrongPassword()],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                // Get or create main tenant
                $tenant = null;
                if ($request->has('tenant_id')) {
                    $tenant = Tenant::findOrFail($request->input('tenant_id'));
                } else {
                    $tenant = Tenant::where('slug', 'main')->first();

                    if (! $tenant) {
                        $tenant = Tenant::create([
                            'name' => env('TENANT_NAME', 'Main Company'),
                            'slug' => 'main',
                            'subdomain' => env('TENANT_SUBDOMAIN', 'main'),
                            'domain' => env('TENANT_DOMAIN'),
                            'status' => 'active',
                            'settings' => ['is_main' => true],
                        ]);
                    }
                }

                // Ensure platform.manage permission exists
                $platformPermission = Permission::firstOrCreate(
                    ['slug' => 'platform.manage'],
                    [
                        'name' => 'Platform - Manage - All',
                        'module' => 'platform',
                        'description' => 'Allows full access to platform-level tenant management',
                    ]
                );

                // Get or create site_owner role
                $siteOwnerRole = Role::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'slug' => 'site_owner',
                    ],
                    [
                        'name' => 'Site Owner',
                        'description' => 'Platform Owner with full access to manage all tenants and system-wide operations',
                        'is_system' => true,
                    ]
                );

                // Assign ALL permissions to site_owner role (including platform.manage)
                $allPermissions = Permission::all();
                $siteOwnerRole->permissions()->sync($allPermissions->pluck('id')->toArray());

                // Create Site Owner user
                $siteOwner = User::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('password')),
                    'tenant_id' => $tenant->id,
                    'email_verified_at' => now(),
                ]);

                // Assign site_owner role to user
                DB::table('user_role')->insert([
                    'user_id' => $siteOwner->id,
                    'role_id' => $siteOwnerRole->id,
                    'tenant_id' => $tenant->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Set as owner of tenant if not already set
                if (! $tenant->owner_user_id) {
                    $tenant->update(['owner_user_id' => $siteOwner->id]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Site Owner created successfully.',
                    'data' => [
                        'id' => $siteOwner->id,
                        'name' => $siteOwner->name,
                        'email' => $siteOwner->email,
                        'tenant' => [
                            'id' => $tenant->id,
                            'name' => $tenant->name,
                            'slug' => $tenant->slug,
                        ],
                        'role' => [
                            'id' => $siteOwnerRole->id,
                            'name' => $siteOwnerRole->name,
                            'slug' => $siteOwnerRole->slug,
                        ],
                        'permissions_count' => $allPermissions->count(),
                        'created_at' => $siteOwner->created_at->toDateTimeString(),
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Site Owner: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all Site Owners.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Check if user is Site Owner
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        $isSiteOwner = $user->hasRole('site_owner') ||
                       $user->hasPermission('platform.manage');

        if (! $isSiteOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Only Site Owners can view this list.',
            ], 403);
        }

        // Get all users with site_owner role
        $siteOwners = User::whereHas('roles', function ($query) {
            $query->where('slug', 'site_owner');
        })
        ->with(['tenant:id,name,slug', 'roles' => function ($query) {
            $query->where('slug', 'site_owner');
        }])
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant' => $user->tenant ? [
                    'id' => $user->tenant->id,
                    'name' => $user->tenant->name,
                    'slug' => $user->tenant->slug,
                ] : null,
                'created_at' => $user->created_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $siteOwners,
            'count' => $siteOwners->count(),
        ]);
    }

    /**
     * Assign platform.manage permission to existing user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignPermission(Request $request): JsonResponse
    {
        // Check if user is Site Owner
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        $isSiteOwner = $user->hasRole('site_owner') ||
                       $user->hasPermission('platform.manage');

        if (! $isSiteOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Only Site Owners can assign platform permissions.',
            ], 403);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $targetUser = User::findOrFail($request->input('user_id'));

                // Ensure platform.manage permission exists
                $platformPermission = Permission::firstOrCreate(
                    ['slug' => 'platform.manage'],
                    [
                        'name' => 'Platform - Manage - All',
                        'module' => 'platform',
                        'description' => 'Allows full access to platform-level tenant management',
                    ]
                );

                // Get user's roles for their tenant
                $userRoles = $targetUser->roles()
                    ->wherePivot('tenant_id', $targetUser->tenant_id)
                    ->get();

                if ($userRoles->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User has no roles. Please assign a role first.',
                    ], 400);
                }

                // Assign platform.manage permission to all user's roles
                foreach ($userRoles as $role) {
                    $role->permissions()->syncWithoutDetaching([$platformPermission->id]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Platform permission assigned successfully.',
                    'data' => [
                        'user_id' => $targetUser->id,
                        'user_name' => $targetUser->name,
                        'user_email' => $targetUser->email,
                        'permission' => 'platform.manage',
                        'roles_updated' => $userRoles->pluck('name')->toArray(),
                    ],
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign permission: ' . $e->getMessage(),
            ], 500);
        }
    }
}

