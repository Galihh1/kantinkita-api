<?php
namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SystemSetting;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey    = config('services.midtrans.server_key');
        Config::$clientKey    = config('services.midtrans.client_key');
        Config::$isProduction = config('services.midtrans.mode') === 'production';
        Config::$isSanitized  = true;
        Config::$is3ds        = true;
    }

    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = config('services.midtrans.server_key');
        $expected  = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        return hash_equals($expected, $signatureKey);
    }

    public function createSnapToken(Order $order): array
    {
        $params = [
            'transaction_details' => [
                'order_id'     => $order->order_number,
                'gross_amount' => (int) $order->grand_total,
            ],
            'customer_details' => [
                'first_name' => $order->user->full_name,
                'email'      => $order->user->email,
                'phone'      => $order->user->phone,
            ],
            'item_details' => $order->items->map(fn($item) => [
                'id'       => $item->menu_id,
                'price'    => (int) $item->price,
                'quantity' => $item->quantity,
                'name'     => $item->menu_name,
            ])->toArray(),
            'expiry' => [
                'unit'     => 'minutes',
                'duration' => (int) SystemSetting::get('payment_timeout', 30),
            ],
        ];

        if ($order->service_fee > 0) {
            $params['item_details'][] = [
                'id'       => 'SERVICE_FEE',
                'price'    => (int) $order->service_fee,
                'quantity' => 1,
                'name'     => SystemSetting::get('fee_label', 'Biaya Layanan'),
            ];
        }

        $snapToken = Snap::getSnapToken($params);

        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'transaction_id' => $order->order_number,
                'status'         => 'pending',
                'gross_amount'   => $order->grand_total,
                'snap_token'     => $snapToken,
                'company_code'   => 'UNIV',
                'created_by'     => auth()->user()?->username ?? 'system',
                'updated_by'     => auth()->user()?->username ?? 'system',
            ]
        );

        $isProduction = config('services.midtrans.mode') === 'production';
        return [
            'snap_token'  => $snapToken,
            'payment_url' => $isProduction
                ? "https://app.midtrans.com/snap/v2/vtweb/{$snapToken}"
                : "https://app.sandbox.midtrans.com/snap/v2/vtweb/{$snapToken}",
        ];
    }

    public function processNotification(array $payload): void
    {
        $order = Order::where('order_number', $payload['order_id'])->first();
        if (!$order) return;

        $payment = Payment::where('order_id', $order->id)->first();
        if (!$payment) return;

        $payment->update(['payment_type' => $payload['payment_type'] ?? null, 'midtrans_response' => $payload, 'updated_by' => 'midtrans_webhook']);

        $transactionStatus = $payload['transaction_status'];
        $fraudStatus       = $payload['fraud_status'] ?? null;
        $newStatus         = null;

        if ($transactionStatus === 'capture') {
            $newStatus = ($fraudStatus === 'accept') ? Order::STATUS_PAID : null;
        } elseif ($transactionStatus === 'settlement') {
            $newStatus = Order::STATUS_PAID;
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $newStatus = Order::STATUS_EXPIRED;
        } elseif ($transactionStatus === 'pending') {
            $newStatus = Order::STATUS_PENDING;
        }

        if ($newStatus && $order->status !== $newStatus) {
            $order->update(['status' => $newStatus, 'updated_by' => 'midtrans_webhook']);

            if ($newStatus === Order::STATUS_PAID) {
                $payment->update(['status' => 'paid', 'paid_at' => now()]);
                event(new \App\Events\NewOrderReceived($order->load(['items', 'user'])));
                app(NotificationService::class)->notifyOrderPaid($order);
            }

            if ($newStatus === Order::STATUS_EXPIRED) {
                $payment->update(['status' => 'expired']);
            }
        }
    }

    public function refund(string $transactionId, float $amount, string $reason): array
    {
        try {
            $refundKey = 'refund-' . $transactionId . '-' . time();
            $response  = Transaction::refund($transactionId, [
                'refund_key' => $refundKey,
                'amount'     => (int) $amount,
                'reason'     => $reason,
            ]);
            return (array) $response;
        } catch (\Exception $e) {
            throw new \Exception('Refund gagal: ' . $e->getMessage());
        }
    }
}
