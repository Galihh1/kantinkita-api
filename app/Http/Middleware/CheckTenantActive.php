<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTenantActive
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user->role === 'owner') {
            // Use fresh query if relationship is null to handle cases where tenant was JUST created
            $tenant = \App\Models\Tenant::where('user_id', $user->id)->first();
            
            if (!$tenant) {
                return response()->json(['status' => false, 'message' => 'SysErr01: Data Tenant Owner tidak ditemukan di database.'], 403);
            }
            // Gunakan loose comparison (==) karena cast boolean
            if ($tenant->status == 0 || $tenant->status == false) {
                return response()->json(['status' => false, 'message' => 'SysErr02: Status Tenant Anda dinonaktifkan.'], 403);
            }
        }

        if ($user->isStaff()) {
            $tenant = $user->staffTenants()->first();
            if (!$tenant || $tenant->status === 0) {
                return response()->json(['status' => false, 'message' => 'Tenant tidak aktif.'], 403);
            }
        }

        return $next($request);
    }
}
