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
        // Gunakan random suffix (uniqid-based) untuk menghindari race condition
        // saat dua checkout terjadi bersamaan pada waktu yang sama.
        $date   = now()->format('Ymd');
        $suffix = strtoupper(substr(str_replace('.', '', uniqid('', true)), -6));
        return 'INV/' . $date . '/' . $suffix;
    }

    public function cancelExpiredOrders(): int
    {
        $orders = Order::where('status', Order::STATUS_PENDING)
                       ->where('expires_at', '<', now())
                       ->get();

        $count = 0;
        /** @var Order $order */
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
