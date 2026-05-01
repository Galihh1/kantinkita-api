<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Tenant;
use App\Models\ActivityLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Mail\OtpMail;
use App\Mail\ResetPasswordMail;


class AuthController extends Controller
{
    use ApiResponse;

    public function checkCompany(Request $request)
    {
        $request->validate([
            'company_code' => 'required|string|max:50',
        ]);

        $code = strtoupper(trim($request->company_code));

        // Special Bypass for System Administrator
        if ($code === 'SYSAD') {
            return $this->success([
                'company_code' => 'SYSAD',
                'company_name' => 'System Administration',
                'tenant_count' => 1,
            ], 'Kode perusahaan valid (Mode Administrator)');
        }

        // Cek apakah company_code terdaftar di tabel tenants (aktif)
        $tenant = Tenant::where('company_code', $code)
            ->where('status', 1)
            ->where('is_deleted', 0)
            ->first();

        if (!$tenant) {
            return $this->error('Kode perusahaan tidak ditemukan atau tidak aktif.', 404);
        }

        $tenantCount = Tenant::where('company_code', $code)
            ->where('status', 1)
            ->where('is_deleted', 0)
            ->count();

        return $this->success([
            'company_code' => $code,
            'company_name' => $tenant->tenant_name,
            'tenant_count' => $tenantCount,
        ], 'Kode perusahaan valid');
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->full_name,
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'company_code' => 'UNIV',
            'created_by' => $request->username,
            'updated_by' => $request->username,
            'status' => 1,
            'profile_completed' => false,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        ActivityLog::record('register', "User baru terdaftar: {$user->email}", $user->id);

        return $this->success(['user' => $user, 'token' => $token], 'Registrasi berhasil', 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->where('is_deleted', 0)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Email atau password salah.', 401);
        }

        if (!$user->status) {
            return $this->error('Akun Anda telah dinonaktifkan.', 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        ActivityLog::record('login', "Login berhasil: {$user->email}", $user->id);

        $user->load(['tenant', 'assignedRole']);
        $user->computed_permissions = $user->getAllPermissions()->pluck('slug');

        return $this->success(['user' => $user, 'token' => $token], 'Login berhasil');
    }

    public function logout(Request $request)
    {
        ActivityLog::record('logout', "Logout: {$request->user()->email}");
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, 'Logout berhasil');
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['tenant', 'assignedRole']);
        $user->computed_permissions = $user->getAllPermissions()->pluck('slug');
        return $this->success($user);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:200',
            'phone' => 'nullable|string|max:20',
            'email_notif' => 'nullable|boolean',
            'wa_notif' => 'nullable|boolean',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = $request->user();
        $data = $request->only(['full_name', 'phone', 'email_notif', 'wa_notif']);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists and is a local file (not a URL)
            if ($user->photo && !filter_var($user->photo, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($user->photo);
            }
            $data['photo'] = $request->file('photo')->store('avatars', 'public');
        }

        $user->update($data);
        ActivityLog::record('update', 'Update profil');

        return $this->success($user->fresh(), 'Profil berhasil diperbarui');
    }

    public function setupProfile(Request $request)
    {
        $user = $request->user();

        if ($user->profile_completed) {
            return $this->error('Profil sudah lengkap.', 400);
        }

        $request->validate([
            'username' => 'required|string|max:100|unique:users,username,' . $user->id,
            'full_name' => 'required|string|max:200',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'no_ktp' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'role' => 'required|in:customer,owner',
            'tenant_name' => 'required_if:role,owner|string|max:200',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $companyCode = 'UNIV';
        $tenant = null;
        if ($request->role === 'owner') {
            $companyCode = $this->generateCompanyCode($request->tenant_name);

            // Calculate trial period from system settings
            $trialDays = (int) \App\Models\SystemSetting::get('trial_days', 30);

            // Create tenant with trial
            $tenant = Tenant::create([
                'user_id' => $user->id,
                'tenant_name' => $request->tenant_name,
                'slug' => Str::slug($request->tenant_name) . '-' . time(),
                'company_code' => $companyCode,
                'status' => 1,
                'trial_ends_at' => now()->addDays($trialDays)->toDateString(),
            ]);
        }

        $user->update([
            'username' => $request->username,
            'full_name' => $request->full_name,
            'name' => $request->full_name,
            'email' => $request->email,
            'no_ktp' => $request->no_ktp,
            'phone' => $request->phone,
            'dob' => $request->dob,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'company_code' => $companyCode,
            'profile_completed' => true,
        ]);

        ActivityLog::record('update', 'Setup profil berhasil');

        $freshUser = $user->fresh();

        // For owner: send company code email
        if ($request->role === 'owner' && $tenant) {
            try {
                Mail::to($freshUser->email)->send(
                    new \App\Mail\TenantRegisteredMail($freshUser, $tenant, $companyCode)
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send tenant registration email: ' . $e->getMessage());
            }
        }

        $responseData = $freshUser->toArray();
        if ($request->role === 'owner' && $tenant) {
            $responseData['company_code'] = $companyCode;
            $responseData['tenant'] = $tenant;
        }

        return $this->success($responseData, 'Setup profil berhasil disimpan');
    }

    private function generateCompanyCode($tenantName)
    {
        $words = explode(' ', trim($tenantName));
        $code = '';
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                // Ambil 1 huruf depan saja
                $code .= strtoupper(substr($word, 0, 1));
            }
        }

        // Pelindung jika nama kosong atau karakter aneh
        if (empty($code)) {
            $code = 'KNTN';
        }

        // Limit code max length if extremely long, e.g., 20 chars
        if (strlen($code) > 20) {
            $code = substr($code, 0, 20);
        }

        // Check uniqueness
        $originalCode = $code;
        while (Tenant::where('company_code', $code)->exists()) {
            // Jika ada yang sama, tambahkan angka acak di belakangnya
            $code = $originalCode . rand(1, 99);
        }

        return $code;
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return $this->error('Password lama tidak sesuai.', 422);
        }

        $request->user()->update(['password' => Hash::make($request->password)]);
        ActivityLog::record('update', 'Ganti password');

        return $this->success(null, 'Password berhasil diubah');
    }

    public function redirectToGoogle()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');
        return $driver->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
            $driver = Socialite::driver('google');
            $googleUser = $driver->stateless()->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Update google_id, ENSURE status is active (1)
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'photo' => $googleUser->getAvatar(),
                    'status' => 1,
                ]);
            } else {
                // Generate a unique username
                $baseUsername = Str::slug(Str::before($googleUser->getEmail(), '@'));
                $username = $baseUsername;
                $counter = 1;
                while (User::where('username', $username)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }

                // Create new user with profile_completed = false
                $user = User::create([
                    'name' => $googleUser->getName() ?? $username,
                    'full_name' => $googleUser->getName() ?? $username,
                    'username' => $username,
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'photo' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(24)),
                    'role' => 'customer',
                    'company_code' => 'UNIV',
                    'created_by' => 'System',
                    'updated_by' => 'System',
                    'profile_completed' => false,
                    'status' => 1,
                ]);
            }

            if (!$user->status) {
                return redirect(config('app.frontend_url', 'http://localhost:5173') . '/login?error=Akun+dinonaktifkan');
            }

            // ── 2FA OTP Logic ─────────────────────────────
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $intentKey = Str::random(40);

            // Simpan OTP di Cache selama 10 menit
            Cache::put("otp_intent:{$intentKey}", [
                'user_id' => $user->id,
                'email' => $user->email,
                'otp' => $otp
            ], now()->addMinutes(10));

            // Kirim Email menggunakan Gmail API (Official HTTPS)
            try {
                $client = new \Google\Client();
                $client->setClientId(env('GMAIL_CLIENT_ID'));
                $client->setClientSecret(env('GMAIL_CLIENT_SECRET'));
                $client->refreshToken(env('GMAIL_REFRESH_TOKEN'));
                
                $service = new \Google\Service\Gmail($client);
                
                $fromEmail = env('MAIL_FROM_ADDRESS', 'pangestu5711@gmail.com');
                $htmlBody = (new OtpMail($user, $otp))->render();
                
                $rawMessage = "From: KantinKita <{$fromEmail}>\r\n";
                $rawMessage .= "To: {$user->email}\r\n";
                $rawMessage .= "Subject: Kode OTP KantinKita\r\n";
                $rawMessage .= "MIME-Version: 1.0\r\n";
                $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
                $rawMessage .= $htmlBody;

                $encodedMessage = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($rawMessage));
                $message = new \Google\Service\Gmail\Message();
                $message->setRaw($encodedMessage);
                
                $service->users_messages->send('me', $message);
            } catch (\Exception $e) {
                \Log::error('Failed to send OTP email via Gmail API: ' . $e->getMessage());
            }

            ActivityLog::record('login_attempt', "OTP dikirim ke: {$user->email}", $user->id);

            // Redirect ke halaman verifikasi OTP di frontend
            $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
            return redirect($frontendUrl . "/auth/otp?email=" . urlencode($user->email) . "&intent=" . $intentKey);

        } catch (\Exception $e) {
            Log::error('Google Login Error: ' . $e->getMessage());
            return redirect(config('app.frontend_url', 'http://localhost:5173') . '/login?error=Otentikasi+Google+Gagal');
        }
    }

    public function verifyGoogleOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'intent' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $cached = Cache::get("otp_intent:{$request->intent}");

        if (!$cached || $cached['email'] !== $request->email) {
            return $this->error('Sesi verifikasi kadaluarsa atau tidak valid.', 422);
        }

        if ($cached['otp'] !== $request->otp) {
            return $this->error('Kode OTP yang Anda masukkan salah.', 422);
        }

        // OTP Valid -> Selesaikan Login
        $user = User::findOrFail($cached['user_id']);

        // Hapus Cache setelah berhasil
        Cache::forget("otp_intent:{$request->intent}");

        $token = $user->createToken('auth_token')->plainTextToken;
        ActivityLog::record('login', "Login Google 2FA Berhasil: {$user->email}", $user->id);

        return $this->success([
            'user' => $user,
            'token' => $token
        ], 'Verifikasi berhasil');
    }

    public function resendGoogleOtp(Request $request)
    {
        $request->validate([
            'email'  => 'required|email',
            'intent' => 'required|string',
        ]);

        $cached = Cache::get("otp_intent:{$request->intent}");

        if (!$cached || $cached['email'] !== $request->email) {
            return $this->error('Sesi verifikasi kadaluarsa. Silakan login ulang.', 422);
        }

        // Generate OTP baru
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Update Cache dengan OTP baru (tetap 10 menit)
        Cache::put("otp_intent:{$request->intent}", [
            'user_id' => $cached['user_id'],
            'email'   => $cached['email'],
            'otp'     => $otp
        ], now()->addMinutes(10));

        // Kirim Email
        $user = User::find($cached['user_id']);
        try {
            $client = new \Google\Client();
            $client->setClientId(env('GMAIL_CLIENT_ID'));
            $client->setClientSecret(env('GMAIL_CLIENT_SECRET'));
            $client->refreshToken(env('GMAIL_REFRESH_TOKEN'));
            
            $service = new \Google\Service\Gmail($client);
            
            $fromEmail = env('MAIL_FROM_ADDRESS', 'pangestu5711@gmail.com');
            $htmlBody = (new OtpMail($user, $otp))->render();
            
            $rawMessage = "From: KantinKita <{$fromEmail}>\r\n";
            $rawMessage .= "To: {$user->email}\r\n";
            $rawMessage .= "Subject: Kode OTP KantinKita (Kirim Ulang)\r\n";
            $rawMessage .= "MIME-Version: 1.0\r\n";
            $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
            $rawMessage .= $htmlBody;

            $encodedMessage = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($rawMessage));
            $message = new \Google\Service\Gmail\Message();
            $message->setRaw($encodedMessage);
            
            $service->users_messages->send('me', $message);

            ActivityLog::record('resend_otp', "OTP dikirim ulang ke: {$user->email}", $user->id);
            return $this->success(null, 'Kode OTP baru telah dikirim ke email Anda.');
        } catch (\Exception $e) {
            \Log::error('Failed to resend OTP email via Gmail API: ' . $e->getMessage());
            return $this->error('Gagal mengirim email. Silakan coba lagi nanti.', 500);
        }
    }


    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        if (!$user->status || $user->is_deleted) {
            return $this->error('Akun tidak ditemukan atau tidak aktif.', 422);
        }

        // Generate token (simple random string or numeric for ease of use)
        $token = strtoupper(Str::random(8));

        // Save to password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        // Send Email
        try {
            $client = new \Google\Client();
            $client->setClientId(env('GMAIL_CLIENT_ID'));
            $client->setClientSecret(env('GMAIL_CLIENT_SECRET'));
            $client->refreshToken(env('GMAIL_REFRESH_TOKEN'));
            
            $service = new \Google\Service\Gmail($client);
            
            $fromEmail = env('MAIL_FROM_ADDRESS', 'pangestu5711@gmail.com');
            $htmlBody = (new ResetPasswordMail($user, $token))->render();
            
            $rawMessage = "From: KantinKita <{$fromEmail}>\r\n";
            $rawMessage .= "To: {$user->email}\r\n";
            $rawMessage .= "Subject: Reset Password KantinKita\r\n";
            $rawMessage .= "MIME-Version: 1.0\r\n";
            $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
            $rawMessage .= $htmlBody;

            $encodedMessage = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($rawMessage));
            $message = new \Google\Service\Gmail\Message();
            $message->setRaw($encodedMessage);
            
            $service->users_messages->send('me', $message);

            ActivityLog::record('forgot_password', "Request reset password untuk: {$user->email}", $user->id);
            return $this->success(null, 'Instruksi reset password telah dikirim ke email Anda.');
        } catch (\Exception $e) {
            Log::error('Forgot Password Email Error: ' . $e->getMessage());
            return $this->error('Gagal mengirim email reset password. Coba lagi nanti.', 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return $this->error('Token reset password tidak valid atau sudah kadaluarsa.', 422);
        }

        // Check expiry (60 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return $this->error('Token reset password sudah kadaluarsa.', 422);
        }

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        // Delete token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        ActivityLog::record('reset_password', "Reset password berhasil: {$user->email}", $user->id);

        return $this->success(null, 'Password Anda berhasil diubah. Silakan login kembali.');
    }
}
