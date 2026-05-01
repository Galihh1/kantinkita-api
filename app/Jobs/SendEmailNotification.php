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

        try {
            $client = new \Google\Client();
            $client->setClientId(config('services.gmail.client_id'));
            $client->setClientSecret(config('services.gmail.client_secret'));
            $client->refreshToken(config('services.gmail.refresh_token'));
            
            $service = new \Google\Service\Gmail($client);
            $fromEmail = config('services.gmail.from_email', 'pangestu5711@gmail.com');
            $htmlBody = view($view, $data)->render();
            
            $rawMessage = "From: KantinKita <{$fromEmail}>\r\n";
            $rawMessage .= "To: {$this->user->email}\r\n";
            $rawMessage .= "Subject: {$subject}\r\n";
            $rawMessage .= "MIME-Version: 1.0\r\n";
            $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
            $rawMessage .= $htmlBody;

            $encodedMessage = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($rawMessage));
            $message = new \Google\Service\Gmail\Message();
            $message->setRaw($encodedMessage);
            
            $service->users_messages->send('me', $message);
        } catch (\Exception $e) {
            \Log::error('Job Email Error: ' . $e->getMessage());
            throw $e; // Rethrow so the job can retry or fail properly
        }
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
