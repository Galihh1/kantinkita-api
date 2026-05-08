<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * GmailService — Satu titik pengelolaan pengiriman email via Gmail API (OAuth2).
 *
 * Sebelumnya kode ini tersebar di 5+ tempat (AuthController, SubscriptionController, dll).
 * Sekarang dipusatkan di sini sehingga bila token expired cukup update 1 tempat.
 */
class GmailService
{
    private \Google\Service\Gmail $service;
    private string $fromEmail;

    public function __construct()
    {
        $client = new \Google\Client();
        $client->setClientId(config('services.gmail.client_id'));
        $client->setClientSecret(config('services.gmail.client_secret'));
        $client->refreshToken(config('services.gmail.refresh_token'));

        $this->service   = new \Google\Service\Gmail($client);
        $this->fromEmail = config('services.gmail.from_email', 'pangestu5711@gmail.com');
    }

    /**
     * Kirim email HTML via Gmail API.
     *
     * @param string $to      Alamat email penerima
     * @param string $subject Subjek email
     * @param string $html    Body email dalam format HTML
     * @throws \Exception     Jika pengiriman gagal (caller bisa catch & log)
     */
    public function send(string $to, string $subject, string $html): void
    {
        $raw  = "From: KantinKita <{$this->fromEmail}>\r\n";
        $raw .= "To: {$to}\r\n";
        $raw .= "Subject: {$subject}\r\n";
        $raw .= "MIME-Version: 1.0\r\n";
        $raw .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $raw .= $html;

        $encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($raw));

        $message = new \Google\Service\Gmail\Message();
        $message->setRaw($encoded);

        $this->service->users_messages->send('me', $message);
    }

    /**
     * Kirim email dengan silent fail — error hanya di-log, tidak melempar exception.
     * Gunakan ini untuk notifikasi non-kritis (misal: notif admin, konfirmasi dsb).
     *
     * @param string $to
     * @param string $subject
     * @param string $html
     * @param string $context Tag untuk Log::warning supaya mudah dicari
     */
    public function sendSilently(string $to, string $subject, string $html, string $context = 'GmailService'): void
    {
        try {
            $this->send($to, $subject, $html);
        } catch (\Exception $e) {
            Log::warning("GMAIL_API_ERROR [{$context}]: " . $e->getMessage(), [
                'to'      => $to,
                'subject' => $subject,
            ]);
        }
    }
}
