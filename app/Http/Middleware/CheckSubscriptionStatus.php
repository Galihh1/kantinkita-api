<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSubscriptionStatus
{
    /**
     * Block write operations (POST/PUT/PATCH/DELETE) if the tenant's
     * trial has expired AND they don't have an active subscription.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only block write/mutation operations
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user) return $next($request);

        // Determine tenant
        $tenant = $user->tenant ?? $user->staffTenants?->first();
        if (!$tenant) return $next($request);

        // Check trial
        $trialActive = $tenant->trial_ends_at && now()->lte($tenant->trial_ends_at);
        if ($trialActive) return $next($request);

        // Check active subscription
        $subscription = $tenant->subscription;
        if ($subscription && $subscription->isActive()) {
            return $next($request);
        }

        // Neither trial nor subscription active
        return response()->json([
            'status'  => false,
            'message' => 'Masa trial telah berakhir. Silakan pilih paket berlangganan untuk melanjutkan.',
            'code'    => 'SUBSCRIPTION_REQUIRED',
        ], 403);
    }
}
