<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\Customer\CartController;
use App\Http\Controllers\Api\Customer\CheckoutController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\Staff\OrderController as StaffOrderController;
use App\Http\Controllers\Api\Staff\MenuController;
use App\Http\Controllers\Api\Owner\ReportController;
use App\Http\Controllers\Api\Owner\OrderController as OwnerOrderController;
use App\Http\Controllers\Api\Owner\RefundController;
use App\Http\Controllers\Api\Owner\StaffController;
use App\Http\Controllers\Api\Owner\SubscriptionController;
use App\Http\Controllers\Api\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\AuditLogController;
use App\Http\Controllers\Api\Admin\ErrorLogController;
use App\Http\Controllers\Api\Admin\BackupController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\DocumentTypeController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\SubscriptionController as AdminSubscriptionController;

// ═══════════════════════════
// HEALTH CHECK (Railway)
// ═══════════════════════════
Route::get('/health', function () {
    try {
        \DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'env' => config('app.env'),
        'database' => $dbStatus,
        'timestamp' => now()->toISOString(),
    ]);
});




// ═══════════════════════════
// PUBLIC ROUTES
// ═══════════════════════════
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/check-company', [AuthController::class, 'checkCompany']);
    Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
    Route::post('/auth/google/verify-otp', [AuthController::class, 'verifyGoogleOtp']);
    Route::post('/auth/google/resend-otp', [AuthController::class, 'resendGoogleOtp']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
});

Route::get('/tenants', [TenantController::class, 'index']);
Route::get('/tenants/{id}', [TenantController::class, 'show']);
Route::get('/tenants/{id}/menus', [TenantController::class, 'menus']);

Route::post('/payment/notification', [PaymentController::class, 'notification']);

// ═══════════════════════════
// AUTHENTICATED ROUTES
// ═══════════════════════════
Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/setup-profile', [AuthController::class, 'setupProfile']);
    Route::put('/auth/password', [AuthController::class, 'changePassword']);

    // ─── TENANT PROFILE ─────────────────────────────
    Route::middleware(['tenant.active'])->group(function () {
        Route::get('/tenant/me', [TenantController::class, 'myTenant']);
        Route::post('/tenant/me', [TenantController::class, 'updateMyTenant']);
    });

    // ─── CUSTOMER ───────────────────────────────────
    Route::middleware(['role:customer'])->prefix('customer')->group(function () {
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart/add', [CartController::class, 'add']);
        Route::put('/cart/{id}', [CartController::class, 'update']);
        Route::delete('/cart/{id}', [CartController::class, 'remove']);
        Route::delete('/cart/clear', [CartController::class, 'clear']);
        Route::post('/checkout', [CheckoutController::class, 'checkout']);
        Route::get('/orders', [CustomerOrderController::class, 'index']);
        Route::get('/orders/{id}', [CustomerOrderController::class, 'show']);
    });

    // ─── STAFF & OWNER (Management) ────────────────
    Route::middleware(['role:staff,owner', 'tenant.active'])->prefix('staff')->group(function () {
        Route::get('/orders', [StaffOrderController::class, 'index'])->middleware('permission:read-pesanan');
        Route::get('/orders/{id}', [StaffOrderController::class, 'show'])->middleware('permission:read-pesanan');
        Route::put('/orders/{id}/status', [StaffOrderController::class, 'updateStatus'])->middleware('permission:update-pesanan');
        Route::post('/orders/{id}/confirm-payment', [StaffOrderController::class, 'confirmPayment'])->middleware('permission:update-pesanan');
        Route::get('/menus', [MenuController::class, 'index'])->middleware('permission:read-menu');
        Route::post('/menus', [MenuController::class, 'store'])->middleware('permission:create-menu');
        Route::put('/menus/{id}', [MenuController::class, 'update'])->middleware('permission:update-menu');
        Route::delete('/menus/{id}', [MenuController::class, 'destroy'])->middleware('permission:delete-menu');
        Route::put('/menus/{id}/availability', [MenuController::class, 'toggleAvailability'])->middleware('permission:update-menu');
        Route::get('/categories', [MenuController::class, 'categories'])->middleware('permission:read-menu');
        Route::post('/categories', [MenuController::class, 'storeCategory'])->middleware('permission:create-menu');
        Route::put('/categories/{id}', [MenuController::class, 'updateCategory'])->middleware('permission:update-menu');
        Route::delete('/categories/{id}', [MenuController::class, 'destroyCategory'])->middleware('permission:delete-menu');
    });

    // ─── OWNER ──────────────────────────────────────
    Route::middleware(['role:owner', 'tenant.active'])->prefix('owner')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:read-laporan');
        Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->middleware('permission:read-laporan');
        Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->middleware('permission:read-laporan');
        Route::get('/orders', [OwnerOrderController::class, 'index'])->middleware('permission:read-pesanan');
        Route::post('/refund', [RefundController::class, 'process'])->middleware('permission:update-pesanan');
        Route::get('/refund/history', [RefundController::class, 'history'])->middleware('permission:read-pesanan');
        Route::get('/staff', [StaffController::class, 'index'])->middleware('permission:read-user');
        Route::post('/staff', [StaffController::class, 'store'])->middleware('permission:create-user');
        Route::put('/staff/{id}', [StaffController::class, 'update'])->middleware('permission:update-user');
        Route::delete('/staff/{id}', [StaffController::class, 'destroy'])->middleware('permission:delete-user');
        Route::put('/staff/{id}/toggle', [StaffController::class, 'toggle'])->middleware('permission:update-user');
        Route::get('/subscription', [SubscriptionController::class, 'index']);
        Route::get('/subscription/plans', [SubscriptionController::class, 'plans']);
        Route::get('/subscription/invoices', [SubscriptionController::class, 'invoices']);
        Route::post('/subscription/subscribe', [SubscriptionController::class, 'subscribe']);
    });

    // ─── ADMIN ──────────────────────────────────────
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/tenants', [AdminTenantController::class, 'index'])->middleware('permission:read-tenant');
        Route::post('/tenants', [AdminTenantController::class, 'store'])->middleware('permission:create-tenant');
        Route::put('/tenants/{id}', [AdminTenantController::class, 'update'])->middleware('permission:update-tenant');
        Route::delete('/tenants/{id}', [AdminTenantController::class, 'destroy'])->middleware('permission:delete-tenant');
        Route::match(['put', 'patch'], '/tenants/{id}/toggle', [AdminTenantController::class, 'toggle'])->middleware('permission:update-tenant');
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:read-user');
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:create-user');
        Route::put('/users/{id}', [UserController::class, 'update'])->middleware('permission:update-user');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('permission:delete-user');
        Route::patch('/users/{id}/toggle', [UserController::class, 'toggle'])->middleware('permission:update-user');
        Route::post('/users/{id}/impersonate', [UserController::class, 'impersonate'])->middleware('permission:update-user');

        Route::get('/settings', [SettingController::class, 'index'])->middleware('permission:read-sistem');
        Route::put('/settings', [SettingController::class, 'update'])->middleware('permission:update-sistem');
        Route::get('/settings/versions', [SettingController::class, 'versions'])->middleware('permission:read-sistem');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:read-sistem');
        Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->middleware('permission:read-sistem');
        Route::get('/error-logs', [ErrorLogController::class, 'index'])->middleware('permission:read-sistem');
        Route::get('/error-logs/stats', [ErrorLogController::class, 'stats'])->middleware('permission:read-sistem');
        Route::match(['put', 'patch'], '/error-logs/{id}/resolve', [ErrorLogController::class, 'resolve'])->middleware('permission:update-sistem');
        Route::get('/backups', [BackupController::class, 'index'])->middleware('permission:read-sistem');
        Route::post('/backups', [BackupController::class, 'create'])->middleware('permission:update-sistem');
        Route::post('/backups/restore', [BackupController::class, 'restore'])->middleware('permission:update-sistem');
        Route::delete('/backups/{filename}', [BackupController::class, 'destroy'])->middleware('permission:delete-sistem');
        Route::get('/backups/{filename}/download', [BackupController::class, 'download'])->middleware('permission:read-sistem');

        Route::apiResource('permissions', PermissionController::class);
        Route::apiResource('roles', RoleController::class);
        Route::post('/roles/{id}/sync', [RoleController::class, 'syncPermissions']);
        Route::post('/roles/seed-defaults', [RoleController::class, 'seedDefaults']);
        Route::apiResource('document-types', DocumentTypeController::class);

        // Subscription Management
        Route::get('/subscriptions', [AdminSubscriptionController::class, 'index']);
        Route::get('/subscriptions/stats', [AdminSubscriptionController::class, 'stats']);
        Route::put('/subscriptions/{id}/approve', [AdminSubscriptionController::class, 'approve']);
        Route::put('/subscriptions/{id}/reject', [AdminSubscriptionController::class, 'reject']);


        Route::get('/reports/aggregate', [ReportController::class, 'aggregate']);
        Route::get('/stats', [AdminTenantController::class, 'stats']);
    });
});

// ═══════════════════════════
// OWNER ACCOUNT SYNC (Best Practice)
// Hanya bisa dipanggil oleh Owner yang login.
// Memperbaiki role_id user agar permissions muncul di sidebar.
// Gunakan setelah admin menyetujui pendaftaran atau jika menu tidak muncul.
// ═══════════════════════════
Route::middleware('auth:sanctum')->post('/owner/sync-my-role', function () {
    $user = request()->user();

    if ($user->role !== 'owner') {
        return response()->json(['status' => false, 'message' => 'Akses ditolak. Hanya Owner yang dapat melakukan sinkronisasi ini.'], 403);
    }

    $hasTenant = \App\Models\Tenant::where('user_id', $user->id)->where('is_deleted', 0)->exists();
    if (!$hasTenant) {
        return response()->json(['status' => false, 'message' => 'Anda tidak terdaftar sebagai pemilik kantin aktif.'], 403);
    }

    $ownerRole = \App\Models\Role::where('slug', 'owner')->first();
    if (!$ownerRole) {
        return response()->json(['status' => false, 'message' => 'Konfigurasi role Owner tidak ditemukan. Hubungi administrator.'], 500);
    }

    $user->update(['role_id' => $ownerRole->id]);

    \App\Models\ActivityLog::record('sync_role', 'Owner melakukan sinkronisasi akun secara mandiri.');

    return response()->json([
        'status'  => true,
        'message' => 'Akun berhasil disinkronkan. Silakan Logout dan Login kembali untuk memperbarui menu.',
        'data'    => ['role_id' => $ownerRole->id, 'role' => 'owner'],
    ]);
});
