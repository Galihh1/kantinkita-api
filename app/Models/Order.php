<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends BaseModel
{
    use HasFactory;


    protected $casts = [
        'total_amount' => 'float',
        'service_fee'  => 'float',
        'grand_total'  => 'float',
        'expires_at'   => 'datetime',
        'refunded_at'  => 'datetime',
    ];

    const STATUS_PENDING    = 'pending_payment';
    const STATUS_PAID       = 'paid';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_EXPIRED    = 'expired';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_REFUNDED   = 'refunded';

    const VALID_TRANSITIONS = [
        self::STATUS_PENDING    => [self::STATUS_EXPIRED, self::STATUS_CANCELLED],
        self::STATUS_PAID       => [self::STATUS_PROCESSING, self::STATUS_REFUNDED],
        self::STATUS_PROCESSING => [self::STATUS_COMPLETED],
    ];

    public function user()    { return $this->belongsTo(User::class); }
    public function tenant()  { return $this->belongsTo(Tenant::class); }
    public function items()   { return $this->hasMany(OrderItem::class); }
    public function payment() { return $this->hasOne(Payment::class); }

    public function isValidTransition(string $newStatus): bool
    {
        return in_array($newStatus, self::VALID_TRANSITIONS[$this->status] ?? []);
    }

    public function scopeByStatus($query, string $status) { return $query->where('status', $status); }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_PENDING)->where('expires_at', '<', now());
    }
}
