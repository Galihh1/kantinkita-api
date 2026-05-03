<?php
namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MidtransService;
use App\Services\OrderService;
use App\Services\NotificationService;
use App\Models\ActivityLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(
        private MidtransService     $midtrans,
        private OrderService        $orderService,
        private NotificationService $notificationService,
    ) {}

    public function checkout(Request $request)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        $cart = Order::where('user_id', $request->user()->id)
            ->where('status', 'cart')
            ->with(['items.menu', 'tenant'])
            ->orderBy('id', 'desc')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return $this->error('Keranjang belanja kosong.', 422);
        }

        if (!$cart->tenant || !$cart->tenant->status) {
            return $this->error('Tenant tidak aktif.', 422);
        }

        if (!$cart->tenant->is_open) {
            return $this->error('Tenant sedang tutup.', 422);
        }

        $minOrder = $cart->tenant->min_order ?? 0;
        if ($cart->total_amount < $minOrder) {
            return $this->error('Minimum order Rp ' . number_format($minOrder, 0, ',', '.'), 422);
        }

        foreach ($cart->items as $item) {
            if (!$item->menu || !$item->menu->is_available) {
                return $this->error("Menu '{$item->menu_name}' sudah tidak tersedia.", 422);
            }
        }

        DB::beginTransaction();
        try {
            $serviceFee  = $this->orderService->calculateFee($cart->total_amount);
            $grandTotal  = $cart->total_amount + $serviceFee;
            $orderNumber = $this->orderService->generateOrderNumber();
            $expiresAt   = now()->addMinutes((int) \App\Models\SystemSetting::get('payment_timeout', 30));

            $cart->update([
                'order_number' => $orderNumber,
                'status'       => Order::STATUS_PENDING,
                'service_fee'  => $serviceFee,
                'grand_total'  => $grandTotal,
                'notes'        => $request->notes,
                'expires_at'   => $expiresAt,
            ]);

            $snapData = $this->midtrans->createSnapToken($cart->fresh(['items', 'user']));
            DB::commit();

            $this->notificationService->notifyOrderCreated($cart);
            ActivityLog::record('checkout', "Checkout order: {$orderNumber}");

            return $this->success([
                'order'       => $cart->fresh(['items', 'tenant', 'payment']),
                'snap_token'  => $snapData['snap_token'],
                'payment_url' => $snapData['payment_url'],
            ], 'Checkout berhasil');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
