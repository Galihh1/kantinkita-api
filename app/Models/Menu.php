<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends BaseModel
{
    use HasFactory;

    protected $appends = ['photo_url'];

    protected $casts = [
        'price'        => 'float',
        'is_available' => 'boolean',
        'status'       => 'boolean',
        'is_deleted'   => 'boolean',
    ];

    public function tenant()     { return $this->belongsTo(Tenant::class); }
    public function category()   { return $this->belongsTo(Category::class); }
    public function orderItems() { return $this->hasMany(OrderItem::class); }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->storageUrl($this->photo);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', 1)->where('status', 1)->where('is_deleted', 0);
    }
}
