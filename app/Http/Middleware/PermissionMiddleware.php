<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        if (!$request->user()) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (!$request->user()->hasPermission($permission)) {
            return response()->json([
                'status' => false, 
                'message' => "Anda tidak memiliki izin '{$permission}' untuk melakukan aksi ini."
            ], 403);
        }

        return $next($request);
    }
}
