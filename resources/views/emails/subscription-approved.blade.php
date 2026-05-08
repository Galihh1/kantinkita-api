<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
        .header { background: linear-gradient(135deg, #2D6A4F, #1B4332); padding: 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 800; }
        .header p { color: rgba(255,255,255,0.7); margin: 8px 0 0; font-size: 13px; }
        .body { padding: 32px; }
        .info-card { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #dcfce7; }
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
            <h1>Langganan Disetujui</h1>
            <p>Selamat! Paket langganan Anda telah aktif</p>
        </div>
        <div class="body">
            <p style="color: #374151; font-size: 14px; line-height: 1.6;">
                Halo <strong>{{ $tenant->owner->full_name ?? $tenant->tenant_name }}</strong>,
                langganan paket <strong>{{ ucfirst($subscription->plan) }}</strong> untuk kantin
                <strong>{{ $tenant->tenant_name }}</strong> telah disetujui oleh Admin.
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
                <div class="info-row">
                    <span class="info-label">Mulai Aktif</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($subscription->billing_start)->format('d M Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Berlaku Hingga</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($subscription->billing_end)->format('d M Y') }}</span>
                </div>
                @if($subscription->admin_notes)
                <div class="info-row">
                    <span class="info-label">Catatan Admin</span>
                    <span class="info-value">{{ $subscription->admin_notes }}</span>
                </div>
                @endif
            </div>
            <p style="color: #6b7280; font-size: 13px; line-height: 1.6;">
                Anda sekarang dapat menggunakan seluruh fitur KantinKita sesuai paket yang dipilih.
                Login ke dashboard Owner untuk mulai mengelola menu dan pesanan.
            </p>
            <div class="cta">
                <a href="{{ config('app.frontend_url', 'https://kantinkita-frontend-production.up.railway.app') }}/owner">Buka Dashboard Owner</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} KantinKita. Email otomatis, jangan dibalas.
        </div>
    </div>
</body>
</html>
