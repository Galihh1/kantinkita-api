<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::saving(function ($user) {
            if ($user->isDirty('role') && !$user->isDirty('role_id')) {
                $roleModel = \App\Models\Role::where('slug', $user->role)->first();
                if ($roleModel) {
                    $user->role_id = $roleModel->id;
                }
            }
        });
    }

    protected $appends = ['photo_url'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_notif'       => 'boolean',
        'wa_notif'          => 'boolean',
        'status'            => 'boolean',
        'is_deleted'        => 'boolean',
        'profile_completed' => 'boolean',
    ];

    public function tenant() { return $this->hasOne(Tenant::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function activityLogs() { return $this->hasMany(ActivityLog::class); }
    public function staffTenants() { return $this->belongsToMany(Tenant::class, 'tenant_user'); }
    public function permissions() { return $this->belongsToMany(Permission::class); }
    public function assignedRole() { return $this->belongsTo(Role::class, 'role_id'); }

    /**
     * Get all permissions for this user (Role + Direct Overrides)
     */
    public function getAllPermissions()
    {
        $rolePermissions = $this->assignedRole ? $this->assignedRole->permissions : collect();
        return $rolePermissions->merge($this->permissions)->unique('id');
    }

    /**
     * Check if user has a specific permission by slug
     */
    public function hasPermission(string $slug): bool
    {
        return $this->getAllPermissions()->contains('slug', $slug);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) return null;

        $url = filter_var($this->photo, FILTER_VALIDATE_URL)
               ? $this->photo
               : asset('storage/' . $this->photo);

        // Force HTTPS di production (Railway mengembalikan http:// dari asset())
        if (app()->environment('production') || str_starts_with(config('app.url', ''), 'https')) {
            $url = str_replace('http://', 'https://', $url);
        }

        return $url;
    }

    public function scopeActive($query) { return $query->where('status', 1)->where('is_deleted', 0); }

    // Logic updated to check role RELATIONSHIP first, fallback to string column
    public function isAdmin(): bool    { return ($this->assignedRole?->slug ?? $this->role) === 'admin'; }
    public function isOwner(): bool    { return ($this->assignedRole?->slug ?? $this->role) === 'owner'; }
    public function isStaff(): bool    { return ($this->assignedRole?->slug ?? $this->role) === 'staff'; }
    public function isCustomer(): bool { return ($this->assignedRole?->slug ?? $this->role) === 'customer'; }
}
