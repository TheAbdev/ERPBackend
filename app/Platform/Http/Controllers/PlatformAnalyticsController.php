<?php

namespace App\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlatformAnalyticsController extends Controller
{
    /**
     * Get analytics overview.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function overview(): JsonResponse
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();
        $totalUsers = User::count();

        // Calculate growth rate (month over month)
        $lastMonth = Carbon::now()->subMonth();
        $tenantsLastMonth = Tenant::where('created_at', '<=', $lastMonth)->count();
        $growthRate = $tenantsLastMonth > 0
            ? (($totalTenants - $tenantsLastMonth) / $tenantsLastMonth) * 100
            : 0;

        return response()->json([
            'data' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'suspended_tenants' => $suspendedTenants,
                'total_users' => $totalUsers,
                'growth_rate' => round($growthRate, 2),
            ],
        ]);
    }

    /**
     * Get tenants growth data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tenantsGrowth(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');

        $startDate = match($period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth(),
        };

        $tenants = Tenant::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $data = $tenants->map(function ($tenant) {
            return [
                'date' => $tenant->date,
                'count' => (int) $tenant->count,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get users growth data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function usersGrowth(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');

        $startDate = match($period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth(),
        };

        $users = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $data = $users->map(function ($user) {
            return [
                'date' => $user->date,
                'count' => (int) $user->count,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get usage by tenant.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function usageByTenant(): JsonResponse
    {
        $tenants = Tenant::withCount('users')->get();

        $data = $tenants->map(function ($tenant) {
            // Users table doesn't have a 'status' column, so we use total users count
            // If you need to track active users, you would need to add a status column or use a different approach
            $usersCount = $tenant->users_count ?? 0;

            return [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'users_count' => $usersCount,
                'active_users_count' => $usersCount, // Since there's no status column, use total count
                'storage_used' => 0, // Placeholder - implement actual storage calculation
                'api_calls_count' => 0, // Placeholder - implement actual API calls tracking
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get tenant usage with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tenantUsage(Request $request): JsonResponse
    {
        $tenants = Tenant::with(['users', 'owner'])
            ->withCount([
                'users',
                'leads',
                'contacts',
                'accounts',
                'deals',
                'activities',
                'notes',
                'salesOrders',
                'invoices',
                'products',
                'projects',
            ])
            ->paginate($request->get('per_page', 15));

        $data = $tenants->map(function ($tenant) {
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'users_count' => $tenant->users_count ?? 0,
                'active_users_count' => $tenant->users_count ?? 0,
                'leads_count' => $tenant->leads_count ?? 0,
                'contacts_count' => $tenant->contacts_count ?? 0,
                'accounts_count' => $tenant->accounts_count ?? 0,
                'deals_count' => $tenant->deals_count ?? 0,
                'activities_count' => $tenant->activities_count ?? 0,
                'notes_count' => $tenant->notes_count ?? 0,
                'sales_orders_count' => $tenant->sales_orders_count ?? 0,
                'invoices_count' => $tenant->invoices_count ?? 0,
                'products_count' => $tenant->products_count ?? 0,
                'projects_count' => $tenant->projects_count ?? 0,
                'storage_used' => 0, // Placeholder - implement actual storage calculation
                'api_calls_count' => 0, // Placeholder - implement actual API calls tracking
                'created_at' => $tenant->created_at,
                'updated_at' => $tenant->updated_at,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
                'last_page' => $tenants->lastPage(),
            ],
        ]);
    }
}

