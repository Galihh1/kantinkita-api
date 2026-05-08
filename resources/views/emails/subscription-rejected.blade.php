<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
        .header { background: linear-gradient(135deg, #b91c1c, #7f1d1d); padding: 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 800; }
        .header p { color: rgba(255,255,255,0.7); margin: 8px 0 0; font-size: 13px; }
        .body { padding: 32px; }
        .info-card { background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #fee2e2; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6b7280; font-size: 13px; }
        .info-value { color: #111827; font-weight: 700; font-size: 13px; }
        .cta { text-align: center; margin-top: 24px; }
        .cta a { display: inline-block; background: #2D6A4F; color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 14px; }
        .footer { text-align: center; padding: 20px; color: #9ca3af; font-size: 11px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pengajuan Langganan Ditolak</h1>
            <p>Pengajuan paket Anda belum dapat disetujui</p>
        </div>
        <div class="body">
            <p style="color: #374151; font-size: 14px; line-height: 1.6;">
                Halo <strong>{{ $tenant->owner->full_name ?? $tenant->tenant_name }}</strong>,
                pengajuan paket <strong>{{ ucfirst($subscription->plan) }}</strong> untuk kantin
                <strong>{{ $tenant->tenant_name }}</strong> tidak dapat disetujui saat ini.
            </p>
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">Nomor Invoice</span>
                    <span class="info-value">{{ $subscription->invoice_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Paket</span>
                    <span class="info-value">{{ ucfirst($subscription->plan) }}</span>
                </div>
                @if($subscription->admin_notes)
                <div class="info-row">
                    <span class="info-label">Alasan Penolakan</span>
                    <span class="info-value">{{ $subscription->admin_notes }}</span>
                </div>
                @endif
            </div>
            <p style="color: #6b7280; font-size: 13px; line-height: 1.6;">
                Anda dapat mengajukan kembali setelah memenuhi persyaratan atau menghubungi tim KantinKita untuk informasi lebih lanjut.
            </p>
            <div class="cta">
                <a href="{{ config('app.frontend_url', 'https://kantinkita-frontend-production.up.railway.app') }}/owner/subscription">Ajukan Ulang</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} KantinKita. Email otomatis, jangan dibalas.
        </div>
    </div>
</body>
</html>
