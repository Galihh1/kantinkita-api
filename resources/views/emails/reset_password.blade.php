<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #eee; border-radius: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #2D6A4F; text-decoration: none; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 8px; text-align: center; }
        .token { font-size: 32px; font-weight: bold; color: #2D6A4F; letter-spacing: 5px; margin: 20px 0; display: block; }
        .footer { text-align: center; font-size: 12px; color: #888; margin-top: 30px; }
        .button { display: inline-block; padding: 12px 24px; background: #2D6A4F; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="#" class="logo">🍽️ KantinKita</a>
        </div>
        <div class="content">
            <h2>Permintaan Reset Password</h2>
            <p>Halo <strong>{{ $user->full_name }}</strong>,</p>
            <p>Kami menerima permintaan untuk mereset password akun Anda. Gunakan kode verifikasi di bawah ini untuk melanjutkan:</p>
            
            <span class="token">{{ $token }}</span>
            
            <p>Atau klik tombol di bawah ini untuk langsung mereset password Anda:</p>
            <a href="http://localhost:5173/reset-password?token={{ $token }}&email={{ urlencode($user->email) }}" class="button">Reset Password Sekarang</a>
            
            <p style="margin-top: 20px; font-size: 13px; color: #666;">
                Kode ini akan kedaluwarsa dalam 60 menit. Jika Anda tidak merasa melakukan permintaan ini, abaikan saja email ini.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} KantinKita. Solusi Digital Kantin Kampus Terintegrasi.
        </div>
    </div>
</body>
</html>
