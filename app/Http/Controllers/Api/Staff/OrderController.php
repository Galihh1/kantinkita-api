<?php
namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ActivityLog;
use App\Services\OrderService;
use App\Services\NotificationService;
use App\Events\OrderStatusChanged;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private OrderService        $orderService,
        private NotificationService $notificationService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $tenant = $user->role === 'owner' ? $user->tenant : $user->staffTenants()->first();
        if (!$tenant) return $this->error('Akses ditolak. Tenant tidak ditemukan.', 403);

        $orders = Order::with(['items.menu', 'user', 'payment'])
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['cart', 'expired', 'cancelled'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date, fn($q) => $q->whereDate('created_at', $request->date))
            ->latest()
            ->paginate(20);

        return $this->success($orders);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $tenant = $user->role === 'owner' ? $user->tenant : $user->staffTenants()->first();
        if (!$tenant) return $this->error('Akses ditolak.', 403);

        $order = Order::with(['items.menu', 'user', 'payment'])
            ->where('id', $id)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        return $this->success($order);
    }

    /**
     * Konfirmasi pembayaran manual (cash / transfer QRIS non-Midtrans).
     * Staff mengkonfirmasi bahwa customer sudah membayar secara langsung.
     */
    public function confirmPayment(Request $request, int $id)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,qris,transfer',
            'notes'          => 'nullable|string|max:255',
        ]);

        $user   = $request->user();
        $tenant = $user->role === 'owner' ? $user->tenant : $user->staffTenants()->first();
        if (!$tenant) return $this->error('Akses ditolak.', 403);

        $order = Order::with(['items', 'user', 'payment'])
            ->where('id', $id)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        if (!in_array($order->status, ['pending', 'processing'])) {
            return $this->error("Tidak bisa konfirmasi pembayaran untuk order dengan status '{$order->status}'.", 422);
        }

        // Update atau buat payment record manual
        $payment = $order->payment ?? new Payment(['order_id' => $order->id]);
        $payment->fill([
            'payment_method' => $request->payment_method,
            'status'         => 'paid',
            'amount'         => $order->total_price,
            'paid_at'        => now(),
            'notes'          => $request->notes ?? "Dikonfirmasi oleh {$user->full_name}",
        ]);
        $payment->save();

        // Update order status → paid
        $order->update([
            'status'     => 'paid',
            'updated_by' => $user->username,
        ]);

        event(new OrderStatusChanged($order->fresh(['items', 'user']), 'paid'));
        $this->notificationService->notifyOrderProcessing($order);

        ActivityLog::record(
            'confirm_payment',
            "Konfirmasi pembayaran {$request->payment_method} untuk order {$order->order_number} oleh {$user->full_name}"
        );

        return $this->success($order->fresh(['items', 'user', 'payment']), 'Pembayaran berhasil dikonfirmasi');
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:processing,completed,cancelled']);

        $user   = $request->user();
        $tenant = $user->role === 'owner' ? $user->tenant : $user->staffTenants()->first();

        $order = Order::where('id', $id)->where('tenant_id', $tenant->id)->firstOrFail();

        if (!$this->orderService->isValidTransition($order->status, $request->status)) {
            return $this->error("Tidak bisa mengubah status dari '{$order->status}' ke '{$request->status}'.", 422);
        }

        $oldStatus = $order->status;
        $order->update(['status' => $request->status, 'updated_by' => $user->username]);

        event(new OrderStatusChanged($order->fresh(['items', 'user']), $request->status));

        match ($request->status) {
            'processing' => $this->notificationService->notifyOrderProcessing($order),
            'completed'  => $this->notificationService->notifyOrderCompleted($order),
            default      => null,
        };

        ActivityLog::record('status_change', "Order {$order->order_number}: {$oldStatus} → {$request->status}");

        return $this->success($order->fresh(['items', 'user', 'payment']), 'Status order berhasil diperbarui');
    }
}
