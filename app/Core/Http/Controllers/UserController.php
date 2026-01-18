<?php

namespace App\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $query = User::with(['tenant', 'roles'])
            ->where('tenant_id', $request->user()->tenant_id);

        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('slug', $request->input('role'))
                  ->wherePivot('tenant_id', $request->user()->tenant_id);
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($request->input('per_page', 15));

        return \App\Core\Http\Resources\UserResource::collection($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) use ($request) {
                    return $query->where('tenant_id', $request->user()->tenant_id);
                }),
            ],
            'password' => 'required|string|min:8|confirmed',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'tenant_id' => $request->user()->tenant_id,
            'is_active' => true, // New users are active by default
        ]);

        // Assign roles if provided
        if ($request->has('role_ids') && !empty($validated['role_ids'])) {
            $user->roles()->attach($validated['role_ids'], [
                'tenant_id' => $request->user()->tenant_id,
            ]);
        }

        // Send welcome email
        try {
            Mail::to($user->email)->send(new WelcomeUserMail($user, $validated['password']));
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Illuminate\Support\Facades\Log::error('Failed to send welcome email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => new \App\Core\Http\Resources\UserResource($user->load(['tenant', 'roles'])),
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load(['tenant', 'roles.permissions']);

        return response()->json([
            'success' => true,
            'data' => new \App\Core\Http\Resources\UserResource($user),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)->where(function ($query) use ($request) {
                    return $query->where('tenant_id', $request->user()->tenant_id);
                }),
            ],
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        // Update user data
        if ($request->has('name')) {
            $user->name = $validated['name'];
        }
        if ($request->has('email')) {
            $user->email = $validated['email'];
        }
        if ($request->has('password') && !empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        // Update roles if provided
        if ($request->has('role_ids')) {
            $user->roles()->wherePivot('tenant_id', $request->user()->tenant_id)->detach();
            if (!empty($validated['role_ids'])) {
                $user->roles()->attach($validated['role_ids'], [
                    'tenant_id' => $request->user()->tenant_id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => new \App\Core\Http\Resources\UserResource($user->fresh()->load(['tenant', 'roles'])),
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        // Prevent deleting yourself
        if ($user->id === request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * Assign roles to user.
     */
    public function assignRoles(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        // Detach existing roles for this tenant
        $user->roles()->wherePivot('tenant_id', $request->user()->tenant_id)->detach();

        // Attach new roles
        $user->roles()->attach($validated['role_ids'], [
            'tenant_id' => $request->user()->tenant_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Roles assigned successfully.',
            'data' => new \App\Core\Http\Resources\UserResource($user->fresh()->load(['tenant', 'roles'])),
        ]);
    }

    /**
     * Activate user.
     */
    public function activate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        // Verify user belongs to same tenant
        if ($user->tenant_id !== request()->user()->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $user->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'User activated successfully.',
            'data' => new \App\Core\Http\Resources\UserResource($user->fresh()->load(['tenant', 'roles'])),
        ]);
    }

    /**
     * Deactivate user.
     */
    public function deactivate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        // Verify user belongs to same tenant
        if ($user->tenant_id !== request()->user()->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Prevent deactivating yourself
        if ($user->id === request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.',
            ], 403);
        }

        $user->update(['is_active' => false]);

        // Revoke all tokens for deactivated user
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deactivated successfully.',
            'data' => new \App\Core\Http\Resources\UserResource($user->fresh()->load(['tenant', 'roles'])),
        ]);
    }
}

