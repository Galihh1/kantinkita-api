<?php
namespace App\Services;

use App\Models\Order;
use App\Models\SystemSetting;

class OrderService
{
    public function calculateFee(float $totalAmount): float
    {
        $feeType  = SystemSetting::get('fee_type', 'percentage');
        $feeValue = (float) SystemSetting::get('fee_value', 0);

        return $feeType === 'percentage'
            ? round($totalAmount * ($feeValue / 100), 2)
            : $feeValue;
    }

    public function generateOrderNumber(): string
    {
        $date      = now()->format('Ymd');
        $lastOrder = Order::whereDate('created_at', today())->orderByDesc('id')->first();
        $sequence  = $lastOrder ? (int) substr($lastOrder->order_number, -4) + 1 : 1;

        return 'INV/' . $date . '/' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function cancelExpiredOrders(): int
    {
        $orders = Order::where('status', Order::STATUS_PENDING)
                       ->where('expires_at', '<', now())
                       ->get();

        $count = 0;
        foreach ($orders as $order) {
            $order->update(['status' => Order::STATUS_EXPIRED, 'updated_by' => 'system_scheduler']);
            if ($order->payment) {
                $order->payment->update(['status' => 'expired', 'updated_by' => 'system_scheduler']);
            }
            $count++;
        }

        return $count;
    }

    public function isValidTransition(string $from, string $to): bool
    {
        return in_array($to, Order::VALID_TRANSITIONS[$from] ?? []);
    }
}
