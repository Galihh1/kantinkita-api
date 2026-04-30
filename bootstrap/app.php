<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\CheckTenantActive;
use App\Http\Middleware\CheckSubscriptionStatus;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'          => RoleMiddleware::class,
            'permission'    => PermissionMiddleware::class,
            'tenant.active'      => CheckTenantActive::class,
            'subscription.check' => CheckSubscriptionStatus::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\LogActivity::class,
        ]);

        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json(['status' => false, 'message' => 'Unauthenticated. Silakan login terlebih dahulu.'], 401);
                }
                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json(['status' => false, 'message' => 'Anda tidak memiliki akses ke resource ini.'], 403);
                }
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json(['status' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
                }
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json(['status' => false, 'message' => 'Data tidak ditemukan.'], 404);
                }
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json(['status' => false, 'message' => 'Endpoint tidak ditemukan.'], 404);
                }
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    return response()->json(['status' => false, 'message' => 'Method tidak didukung untuk rute ini.'], 405);
                }
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
                    return response()->json(['status' => false, 'message' => 'Terlalu banyak request. Coba lagi nanti.'], 429);
                }

                try {
                    \App\Models\ErrorLog::create([
                        'user_id'         => auth()->id(),
                        'level'           => 'error',
                        'message'         => $e->getMessage(),
                        'stack_trace'     => $e->getTraceAsString(),
                        'endpoint'        => $request->fullUrl(),
                        'ip_address'      => $request->ip(),
                        'resolved_status' => 'open',
                        'company_code'    => 'UNIV',
                    ]);
                } catch (\Exception $logError) {
                    // Silent fail
                }

                if (config('app.debug')) {
                    return response()->json([
                        'status'  => false,
                        'message' => $e->getMessage(),
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                    ], 500);
                }

                return response()->json(['status' => false, 'message' => 'Terjadi kesalahan pada server.'], 500);
            }
        });
    })
    ->create();
