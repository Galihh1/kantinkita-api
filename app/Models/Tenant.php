<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends BaseModel
{
    use HasFactory;

    protected $appends = ['photo_url'];

    protected $casts = [
        'is_open'       => 'boolean',
        'min_order'     => 'float',
        'open_hours'    => 'array',
        'status'        => 'boolean',
        'is_deleted'    => 'boolean',
        'trial_ends_at' => 'date',
    ];

    public function owner()        { return $this->belongsTo(User::class, 'user_id'); }
    public function categories()   { return $this->hasMany(Category::class)->active(); }
    public function menus()        { return $this->hasMany(Menu::class)->active(); }
    public function orders()       { return $this->hasMany(Order::class); }
    public function subscription() { return $this->hasOne(Subscription::class)->latest(); }
    public function staff()        { return $this->belongsToMany(User::class, 'tenant_user'); }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->storageUrl($this->photo);
    }
}
