<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\ActivityLog;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $subscriptions = Subscription::with(['tenant.owner'])
            ->when($request->status, fn($q) => $q->where('approval_status', $request->status))
            ->when($request->search, fn($q) =>
                $q->whereHas('tenant', fn($q2) =>
                    $q2->where('tenant_name', 'like', "%{$request->search}%")
                )
            )
            ->orderByRaw("FIELD(approval_status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->success($subscriptions);
    }

    public function approve(Request $request, int $id)
    {
        $subscription = Subscription::with('tenant')->findOrFail($id);

        if ($subscription->approval_status === 'approved') {
            return $this->error('Langganan ini sudah disetujui.', 422);
        }

        $request->validate([
            'duration_months' => 'required|integer|min:1|max:24',
            'admin_notes'     => 'nullable|string|max:500',
        ]);

        $months = $request->duration_months;
        $now = now();

        $subscription->update([
            'approval_status' => 'approved',
            'billing_status'  => 'active',
            'billing_start'   => $now->toDateString(),
            'billing_end'     => $now->copy()->addMonths($months)->toDateString(),
            'admin_notes'     => $request->admin_notes,
            'approved_by'     => $request->user()->id,
        ]);

        ActivityLog::record('update', "Admin menyetujui langganan #{$subscription->id} untuk tenant: {$subscription->tenant->tenant_name} ({$months} bulan)");

        return $this->success($subscription->fresh()->load('tenant.owner'), 'Langganan berhasil disetujui');
    }

    public function reject(Request $request, int $id)
    {
        $subscription = Subscription::with('tenant')->findOrFail($id);

        if ($subscription->approval_status !== 'pending') {
            return $this->error('Hanya langganan dengan status pending yang dapat ditolak.', 422);
        }

        $request->validate([
            'admin_notes' => 'required|string|max:500',
        ]);

        $subscription->update([
            'approval_status' => 'rejected',
            'billing_status'  => 'cancelled',
            'admin_notes'     => $request->admin_notes,
            'approved_by'     => $request->user()->id,
        ]);

        ActivityLog::record('update', "Admin menolak langganan #{$subscription->id} untuk tenant: {$subscription->tenant->tenant_name}");

        return $this->success($subscription->fresh()->load('tenant.owner'), 'Langganan ditolak');
    }

    public function stats()
    {
        return $this->success([
            'pending'  => Subscription::where('approval_status', 'pending')->count(),
            'approved' => Subscription::where('approval_status', 'approved')->count(),
            'rejected' => Subscription::where('approval_status', 'rejected')->count(),
            'active'   => Subscription::where('billing_status', 'active')
                ->where('billing_end', '>=', now()->toDateString())->count(),
        ]);
    }
}
