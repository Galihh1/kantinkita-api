<?php
namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        public User   $user,
        public Order  $order,
        public string $template,
    ) {}

    public function handle(): void
    {
        $subject = match ($this->template) {
            'order_created'    => "Order #{$this->order->order_number} Berhasil Dibuat",
            'order_paid'       => "Pembayaran Order #{$this->order->order_number} Berhasil",
            'order_processing' => "Order #{$this->order->order_number} Sedang Diproses",
            'order_completed'  => "Order #{$this->order->order_number} Selesai",
            'new_order_staff'  => "Pesanan Baru Masuk: #{$this->order->order_number}",
            default            => "Update Order #{$this->order->order_number}",
        };

        $data = ['user' => $this->user, 'order' => $this->order->load(['items', 'tenant']), 'template' => $this->template];

        // Only send if view exists to prevent errors
        $view = "emails.{$this->template}";
        if (!view()->exists($view)) return;

        Mail::send($view, $data, function ($mail) use ($subject) {
            $mail->to($this->user->email, $this->user->full_name)
                 ->subject($subject)
                 ->from(config('mail.from.address'), config('mail.from.name'));
        });
    }

    public function failed(\Throwable $exception): void
    {
        \App\Models\ErrorLog::create([
            'user_id'         => $this->user->id,
            'level'           => 'error',
            'message'         => "Email gagal: " . $exception->getMessage(),
            'stack_trace'     => $exception->getTraceAsString(),
            'endpoint'        => 'queue:SendEmailNotification',
            'resolved_status' => 'open',
            'company_code'    => 'UNIV',
        ]);
    }
}
