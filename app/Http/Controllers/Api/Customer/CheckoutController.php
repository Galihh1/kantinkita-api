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
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'items'     => 'required|array|min:1',
            'items.*.menu_id'  => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes'     => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $tenant = \App\Models\Tenant::findOrFail($request->tenant_id);
            
            if (!$tenant->status) return $this->error('Tenant tidak aktif.', 422);
            if (!$tenant->is_open)   return $this->error('Tenant sedang tutup.', 422);

            // Create or get cart
            $cart = Order::where('user_id', $request->user()->id)
                ->where('status', 'cart')
                ->first();

            if (!$cart) {
                $cart = Order::create([
                    'order_number' => 'TEMP-' . time(),
                    'user_id'      => $request->user()->id,
                    'tenant_id'    => $tenant->id,
                    'status'       => 'cart',
                    'company_code' => 'UNIV',
                ]);
            }

            // Sync items from request
            $cart->items()->delete();
            $totalAmount = 0;
            foreach ($request->items as $itemData) {
                $menu = \App\Models\Menu::findOrFail($itemData['menu_id']);
                if (!$menu->is_available) {
                    throw new \Exception("Menu '{$menu->name}' sudah tidak tersedia.");
                }
                $subtotal = $menu->price * $itemData['quantity'];
                $cart->items()->create([
                    'menu_id'      => $menu->id,
                    'menu_name'    => $menu->name,
                    'price'        => $menu->price,
                    'quantity'     => $itemData['quantity'],
                    'subtotal'     => $subtotal,
                    'company_code' => 'UNIV',
                ]);
                $totalAmount += $subtotal;
            }

            if ($totalAmount < ($tenant->min_order ?? 0)) {
                throw new \Exception('Minimum order Rp ' . number_format($tenant->min_order, 0, ',', '.'));
            }

            $serviceFee  = $this->orderService->calculateFee($totalAmount);
            $grandTotal  = $totalAmount + $serviceFee;
            $orderNumber = $this->orderService->generateOrderNumber();
            $expiresAt   = now()->addMinutes((int) \App\Models\SystemSetting::get('payment_timeout', 30));

            $cart->update([
                'order_number' => $orderNumber,
                'status'       => Order::STATUS_PENDING,
                'total_amount' => $totalAmount,
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
            return $this->error($e->getMessage(), 422);
        }
    }
}
