<?php
namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PackageRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $plan,
        public int $amount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[KantinKita] Pengajuan Paket Baru - {$this->tenant->tenant_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.package-requested',
        );
    }
}
