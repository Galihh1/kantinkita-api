<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $tenant;
    public $companyCode;

    public function __construct($user, $tenant, $companyCode)
    {
        $this->user = $user;
        $this->tenant = $tenant;
        $this->companyCode = $companyCode;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'KantinKita - Registrasi Tenant Berhasil | Kode Perusahaan Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-registered',
        );
    }
}
