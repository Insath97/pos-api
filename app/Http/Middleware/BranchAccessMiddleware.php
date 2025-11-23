<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BranchAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $userBranchId = $user->branch_id;

        $requestedBranchId = $request->route('branch') ??
            $request->route('id') ??
            $request->branch_id ??
            $request->input('branch_id');

        if (!$userBranchId) {
            return response()->json([
                'error' => 'User not assigned to any branch'
            ], 403);
        }

        if (!$requestedBranchId) {
            return $next($request);
        }

        if ($userBranchId != $requestedBranchId) {
            return response()->json([
                'error' => 'Access denied to this branch'
            ], 403);
        }

        return $next($request);
    }
}
