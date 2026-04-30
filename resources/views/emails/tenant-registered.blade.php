<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>KantinKita - Registrasi Tenant</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h2 style="color: #2D6A4F; margin-bottom: 20px;">Selamat Bergabung di KantinKita!</h2>
        
        <p>Halo <strong>{{ $user->full_name }}</strong>,</p>
        
        <p>Pendafataran kantin Anda <strong>{{ $tenant->tenant_name }}</strong> telah berhasil kami terima.</p>
        
        <div style="background-color: #f0fdf4; border-left: 4px solid #2D6A4F; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; color: #4b5563;">Kode Perusahaan / Instansi Anda:</p>
            <h1 style="margin: 5px 0 0 0; color: #1a4731; letter-spacing: 2px;">{{ $companyCode }}</h1>
        </div>
        
        <p>Gunakan kode ini saat melakukan verifikasi login. Harap simpan kode ini dengan baik dan berikan kepada staff kantin Anda saat mereka mendaftar di sistem.</p>
        
        <p style="margin-top: 30px;"><strong>Langkah Selanjutnya:</strong></p>
        <p>Untuk mengamankan akun Anda dan menyelesaikan proses verifikasi, silakan klik tombol di bawah ini untuk mengatur Autentikasi 2 Langkah (2FA).</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url(env('FRONTEND_URL', 'http://localhost:5173') . '/2fa-setup?code=' . $companyCode) }}" style="background-color: #2D6A4F; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Verifikasi Akun & Setup 2FA</a>
        </div>
        
        <p style="color: #6b7280; font-size: 13px; border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 40px;">
            Jika Anda mengalami kendala atau tidak merasa mendaftar di platform kami, silakan abaikan email ini atau hubungi dukungan admin KantinKita.
        </p>
    </div>
</body>
</html>
