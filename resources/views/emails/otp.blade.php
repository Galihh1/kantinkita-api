<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kode Verifikasi KantinKita</title>
    <style>
        body { font-family: 'Inter', -apple-system, sans-serif; background-color: #f9fafb; color: #1f2937; line-height: 1.5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 20px; border: 1px solid #f1f5f9; overflow: hidden; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .header { background: #2D6A4F; padding: 40px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.025em; }
        .content { padding: 40px; text-align: center; }
        .greeting { font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 8px; }
        .description { color: #64748b; font-size: 15px; margin-bottom: 32px; }
        .otp-container { background: #f0fdf4; border: 2px dashed #bbf7d0; border-radius: 12px; padding: 24px; margin-bottom: 32px; }
        .otp-code { font-size: 36px; font-weight: 800; letter-spacing: 0.25em; color: #2D6A4F; font-family: 'Courier New', Courier, monospace; }
        .footer { padding: 24px; background: #f8fafc; text-align: center; border-top: 1px solid #f1f5f9; }
        .footer p { color: #94a3b8; font-size: 13px; margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>KantinKita</h1>
        </div>
        <div class="content">
            <p class="greeting">Halo, {{ $user->full_name }}!</p>
            <p class="description">Anda baru saja mencoba masuk ke KantinKita menggunakan Google. Gunakan kode verifikasi di bawah ini untuk melanjutkan:</p>
            
            <div class="otp-container">
                <span class="otp-code">{{ $otp }}</span>
            </div>

            <p class="description" style="font-size: 13px;">Kode ini berlaku selama 10 menit. Jika Anda tidak merasa mencoba masuk, harap abaikan email ini.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} KantinKita. Solusi Digital Kantin Kampus.</p>
        </div>
    </div>
</body>
</html>
