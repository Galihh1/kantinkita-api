<?php
namespace App\Services;

use App\Models\Order;
use App\Models\SystemSetting;
use App\Jobs\SendEmailNotification;
use App\Jobs\SendWhatsAppNotification;

class NotificationService
{
    public function notifyOrderCreated(Order $order): void
    {
        if (!SystemSetting::get('notif_order_created', true)) return;
        $this->sendToCustomer($order, 'order_created');
    }

    public function notifyOrderPaid(Order $order): void
    {
        if (!SystemSetting::get('notif_order_paid', true)) return;
        $this->sendToCustomer($order, 'order_paid');
        $this->sendToStaff($order, 'new_order_staff');
    }

    public function notifyOrderProcessing(Order $order): void
    {
        if (!SystemSetting::get('notif_order_processing', true)) return;
        $this->sendToCustomer($order, 'order_processing');
    }

    public function notifyOrderCompleted(Order $order): void
    {
        if (!SystemSetting::get('notif_order_completed', true)) return;
        $this->sendToCustomer($order, 'order_completed');
    }

    private function sendToCustomer(Order $order, string $template): void
    {
        $user = $order->user;
        if ($user->email_notif) {
            SendEmailNotification::dispatch($user, $order, $template)->onQueue('notifications');
        }
        if ($user->wa_notif && $user->phone) {
            SendWhatsAppNotification::dispatch($user, $order, $template)->onQueue('notifications');
        }
    }

    private function sendToStaff(Order $order, string $template): void
    {
        foreach ($order->tenant->staff as $staff) {
            if ($staff->email_notif) {
                SendEmailNotification::dispatch($staff, $order, $template)->onQueue('notifications');
            }
            if ($staff->wa_notif && $staff->phone) {
                SendWhatsAppNotification::dispatch($staff, $order, $template)->onQueue('notifications');
            }
        }
    }

    public function sendWhatsApp(string $phone, string $message): void
    {
        $token = config('services.fonnte.token');
        if (!$token) return;

        $phone = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $phone));

        try {
            \Illuminate\Support\Facades\Http::withHeaders(['Authorization' => $token])
                ->post(config('services.fonnte.url', 'https://api.fonnte.com/send'), [
                    'target'  => $phone,
                    'message' => $message,
                ]);
        } catch (\Exception $e) {
            // Silent fail
        }
    }
}
