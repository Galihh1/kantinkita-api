<?php
namespace App\Models;

class Payment extends BaseModel
{
    protected $casts = [
        'gross_amount'      => 'float',
        'midtrans_response' => 'array',
        'paid_at'           => 'datetime',
    ];

    public function order() { return $this->belongsTo(Order::class); }
}
