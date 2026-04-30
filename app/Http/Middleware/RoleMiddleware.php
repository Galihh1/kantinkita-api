<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!$request->user()) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $userRole = $request->user()->assignedRole?->slug ?? $request->user()->role;

        if (!in_array($userRole, $roles)) {
            return response()->json(['status' => false, 'message' => 'Anda tidak memiliki akses ke resource ini.'], 403);
        }

        return $next($request);
    }
}
