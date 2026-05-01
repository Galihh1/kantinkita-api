<?php
namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\ActivityLog;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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

        // Send notification email to admins
        try {
            $client = new \Google\Client();
            $client->setClientId(config('services.gmail.client_id'));
            $client->setClientSecret(config('services.gmail.client_secret'));
            $client->refreshToken(config('services.gmail.refresh_token'));
            
            $service = new \Google\Service\Gmail($client);
            $fromEmail = config('services.gmail.from_email', 'pangestu5711@gmail.com');
            
            $admins = User::where('role', 'admin')->where('status', 1)->where('is_deleted', 0)->get();
            foreach ($admins as $admin) {
                $htmlBody = view('emails.package-requested', ['tenant' => $tenant, 'plan' => $request->plan, 'amount' => $prices[$request->plan]])->render();
                
                $rawMessage = "From: KantinKita <{$fromEmail}>\r\n";
                $rawMessage .= "To: {$admin->email}\r\n";
                $rawMessage .= "Subject: Pengajuan Paket Baru: {$request->plan}\r\n";
                $rawMessage .= "MIME-Version: 1.0\r\n";
                $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
                $rawMessage .= $htmlBody;

                $encodedMessage = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($rawMessage));
                $message = new \Google\Service\Gmail\Message();
                $message->setRaw($encodedMessage);
                
                $service->users_messages->send('me', $message);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send package request email via Gmail API: ' . $e->getMessage());
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
        $plans = [
            [
                'id' => 'starter',
                'name' => 'Starter',
                'price' => (int) SystemSetting::get('price_starter', 99000),
                'is_recommended' => false,
                'features' => ['100 Orders/bulan', '50 Menu', '2 Staff Accounts', 'Basic Reporting']
            ],
            [
                'id' => 'professional',
                'name' => 'Professional',
                'price' => (int) SystemSetting::get('price_professional', 299000),
                'is_recommended' => true,
                'features' => ['Unlimited Orders', 'Unlimited Menu', '10 Staff Accounts', 'Advanced Reporting', 'Priority Support']
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise',
                'price' => (int) SystemSetting::get('price_enterprise', 799000),
                'is_recommended' => false,
                'features' => ['Custom Limit', 'Custom Domain', 'Unlimited Staff', 'Dedicated Account Manager', 'API Access']
            ]
        ];

        return $this->success($plans);
    }
}
