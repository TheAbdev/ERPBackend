<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\UserLoginHistory;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LoginHistoryController extends Controller
{
    /**
     * Display login history for authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $history = UserLoginHistory::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->orderBy('logged_in_at', 'desc')
            ->paginate();

        return \App\Core\Http\Resources\UserLoginHistoryResource::collection($history);
    }

    /**
     * Display login history for all users (admin only).
     */
    public function all(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', UserLoginHistory::class);

        $user = $request->user();

        $history = UserLoginHistory::where('tenant_id', $user->tenant_id)
            ->with('user')
            ->orderBy('logged_in_at', 'desc')
            ->paginate();

        return \App\Core\Http\Resources\UserLoginHistoryResource::collection($history);
    }
}

