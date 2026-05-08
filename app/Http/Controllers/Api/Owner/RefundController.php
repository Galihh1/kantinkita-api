<?php
namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use App\Models\ActivityLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    use ApiResponse;

    public function __construct(private MidtransService $midtrans) {}

    public function process(Request $request)
    {
        $request->validate([
            'order_id'      => 'required|exists:orders,id',
            'refund_reason' => 'required|string|min:10|max:500',
        ]);

        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);

        $order = Order::where('id', $request->order_id)
                      ->where('tenant_id', $tenant->id)
                      ->firstOrFail();

        if (!in_array($order->status, [Order::STATUS_PAID, Order::STATUS_PROCESSING])) {
            return $this->error('Order tidak dapat direfund. Status saat ini: ' . $order->status, 422);
        }

        $payment = $order->payment;
        if (!$payment || !$payment->transaction_id) {
            return $this->error('Data pembayaran tidak ditemukan.', 404);
        }

        try {
            $refundResult = $this->midtrans->refund(
                $payment->transaction_id,
                $order->grand_total,
                $request->refund_reason
            );

            $order->update([
                'status'        => Order::STATUS_REFUNDED,
                'refund_reason' => $request->refund_reason,
                'refunded_at'   => now(),
                'updated_by'    => $request->user()->username,
            ]);

            $payment->update(['status' => 'refunded', 'updated_by' => $request->user()->username]);

            ActivityLog::record('refund', "Refund order {$order->order_number}: {$request->refund_reason}");

            return $this->success([
                'order'  => $order->fresh(['items', 'user', 'payment']),
                'refund' => $refundResult,
            ], 'Refund berhasil diproses');
        } catch (\Exception $e) {
            return $this->error('Refund gagal: ' . $e->getMessage(), 500);
        }
    }

    public function history(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);

        $orders = Order::where('tenant_id', $tenant->id)
                       ->where('status', Order::STATUS_REFUNDED)
                       ->with(['user', 'payment'])
                       ->orderByDesc('refunded_at')
                       ->paginate(20);

        return $this->success($orders);
    }
}
