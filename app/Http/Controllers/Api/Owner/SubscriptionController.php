<?php
namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\GmailService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);

        $subscription = $tenant->subscription;
        $plans = [
            'starter'      => (int) SystemSetting::get('price_starter', 99000),
            'professional' => (int) SystemSetting::get('price_professional', 299000),
            'enterprise'   => (int) SystemSetting::get('price_enterprise', 799000),
        ];

        // Check trial status
        $trialActive = $tenant->trial_ends_at && now()->lte($tenant->trial_ends_at);
        $trialDaysRemaining = $tenant->trial_ends_at ? now()->diffInDays($tenant->trial_ends_at, false) : null;

        return $this->success([
            'has_subscription'     => (bool) $subscription,
            'subscription'         => $subscription,
            'is_active'            => $subscription?->isActive() ?? false,
            'is_expiring_soon'     => $subscription?->isExpiringSoon() ?? false,
            'days_remaining'       => $subscription ? now()->diffInDays($subscription->billing_end, false) : null,
            'plans'                => $plans,
            'trial_active'         => $trialActive,
            'trial_ends_at'        => $tenant->trial_ends_at,
            'trial_days_remaining' => $trialDaysRemaining,
        ]);
    }

    public function subscribe(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);

        $request->validate([
            'plan' => 'required|in:starter,professional,enterprise',
        ]);

        // Check if there's already a pending subscription
        $pending = Subscription::where('tenant_id', $tenant->id)
            ->where('approval_status', 'pending')
            ->first();

        if ($pending) {
            return $this->error('Anda sudah memiliki pengajuan paket yang sedang menunggu persetujuan.', 422);
        }

        $prices = [
            'starter'      => (int) SystemSetting::get('price_starter', 99000),
            'professional' => (int) SystemSetting::get('price_professional', 299000),
            'enterprise'   => (int) SystemSetting::get('price_enterprise', 799000),
        ];

        $subscription = Subscription::create([
            'tenant_id'       => $tenant->id,
            'plan'            => $request->plan,
            'amount'          => $prices[$request->plan],
            'billing_status'  => 'pending',
            'approval_status' => 'pending',
            'invoice_number'  => 'INV-' . strtoupper(uniqid()),
            'company_code'    => $tenant->company_code ?? 'UNIV',
            'created_by'      => $request->user()->username,
            'updated_by'      => $request->user()->username,
        ]);

        ActivityLog::record('create', "Owner mengajukan paket {$request->plan} untuk tenant: {$tenant->tenant_name}");

        // Notifikasi email ke semua admin via GmailService
        $admins = User::where('role', 'admin')->where('status', 1)->where('is_deleted', 0)->get();
        foreach ($admins as $admin) {
            app(GmailService::class)->sendSilently(
                $admin->email,
                "Pengajuan Paket Baru: {$request->plan}",
                view('emails.package-requested', ['tenant' => $tenant, 'plan' => $request->plan, 'amount' => $prices[$request->plan]])->render(),
                'owner.subscribe'
            );
        }

        return $this->success($subscription, 'Pengajuan paket berhasil dikirim. Menunggu persetujuan admin.', 201);
    }

    public function invoices(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);

        $invoices = Subscription::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->success($invoices);
    }

    public function plans()
    {
        // Fitur paket dibaca dari system_settings agar bisa diubah dari frontend
        $parseFeatures = fn(string $key, string $default) =>
            array_map('trim', explode(',', SystemSetting::get($key, $default)));

        $plans = [
            [
                'id'             => 'starter',
                'name'           => 'Starter',
                'price'          => (int) SystemSetting::get('price_starter', 99000),
                'is_recommended' => false,
                'features'       => $parseFeatures('plan_starter_features', '100 Orders/bulan,50 Menu,2 Staff Accounts,Basic Reporting'),
            ],
            [
                'id'             => 'professional',
                'name'           => 'Professional',
                'price'          => (int) SystemSetting::get('price_professional', 299000),
                'is_recommended' => true,
                'features'       => $parseFeatures('plan_professional_features', 'Unlimited Orders,Unlimited Menu,10 Staff Accounts,Advanced Reporting,Priority Support'),
            ],
            [
                'id'             => 'enterprise',
                'name'           => 'Enterprise',
                'price'          => (int) SystemSetting::get('price_enterprise', 799000),
                'is_recommended' => false,
                'features'       => $parseFeatures('plan_enterprise_features', 'Custom Limit,Custom Domain,Unlimited Staff,Dedicated Account Manager,API Access'),
            ],
        ];

        return $this->success($plans);
    }
}
