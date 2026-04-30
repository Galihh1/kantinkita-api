<?php
namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        public User   $user,
        public Order  $order,
        public string $template,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $message = $this->buildMessage();
        if ($message) $notificationService->sendWhatsApp($this->user->phone, $message);
    }

    private function buildMessage(): string
    {
        $order = $this->order->load(['items', 'tenant']);
        $items = $order->items->map(fn($i) => "- {$i->menu_name} x{$i->quantity} = Rp " . number_format($i->subtotal, 0, ',', '.'))->implode("\n");
        $total = 'Rp ' . number_format($order->grand_total, 0, ',', '.');

        return match ($this->template) {
            'order_created'    => "🛒 *Pesanan Dibuat*\n\nHalo {$this->user->full_name}!\n📋 No. Order: *{$order->order_number}*\n🏪 {$order->tenant->tenant_name}\n\n📦 Item:\n{$items}\n\n💰 Total: *{$total}*\n\nSegera lakukan pembayaran!",
            'order_paid'       => "✅ *Pembayaran Berhasil*\n\nHalo {$this->user->full_name}!\n📋 No. Order: *{$order->order_number}*\n💰 Total: *{$total}*\n\nPesanan Anda sedang menunggu diproses.",
            'order_processing' => "👨🍳 *Pesanan Sedang Diproses*\n\nHalo {$this->user->full_name}!\nPesanan *{$order->order_number}* sedang dimasak.\nMohon tunggu sebentar 😊",
            'order_completed'  => "🎉 *Pesanan Selesai*\n\nHalo {$this->user->full_name}!\nPesanan *{$order->order_number}* telah selesai.\nSilakan ambil pesanan Anda.\nTerima kasih! 🙏",
            'new_order_staff'  => "🔔 *Pesanan Baru!*\n\n📋 No. Order: *{$order->order_number}*\n👤 {$order->user->full_name}\n\n📦 Item:\n{$items}\n\n💰 Total: *{$total}*" . ($order->notes ? "\n📝 Catatan: {$order->notes}" : "") . "\n\nSegera proses pesanan ini!",
            default            => '',
        };
    }

    public function failed(\Throwable $exception): void
    {
        \App\Models\ErrorLog::create([
            'user_id'         => $this->user->id,
            'level'           => 'warning',
            'message'         => "WhatsApp gagal: " . $exception->getMessage(),
            'endpoint'        => 'queue:SendWhatsAppNotification',
            'resolved_status' => 'open',
            'company_code'    => 'UNIV',
        ]);
    }
}
