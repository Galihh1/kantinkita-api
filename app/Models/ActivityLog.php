<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $guarded = ['id'];

    public function user() { return $this->belongsTo(User::class); }

    public static function record(string $action, string $description, ?int $userId = null, ?int $status = null): void
    {
        $finalUserId = $userId ?? auth()->id();
        $finalDescription = $description;

        // Check if current action is performed via impersonation
        $user = auth()->user();
        if ($user && method_exists($user, 'currentAccessToken')) {
            $token = $user->currentAccessToken();
            if ($token && str_starts_with($token->name, 'impersonated_by_')) {
                $adminId = str_replace('impersonated_by_', '', $token->name);
                $finalDescription .= " (via Admin ID: {$adminId})";
            }
        }

        static::create([
            'user_id'      => $finalUserId,
            'action'       => $action,
            'description'  => $finalDescription,
            'method'       => request()->method(),
            'url'          => request()->fullUrl(),
            'status_code'  => $status ?? (isset($GLOBALS['response_status']) ? $GLOBALS['response_status'] : 200),
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'company_code' => 'UNIV',
        ]);
    }
}
