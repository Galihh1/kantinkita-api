<?php
namespace App\Models;

class OrderItem extends BaseModel
{
    protected $casts = ['price' => 'float', 'subtotal' => 'float'];

    public function order() { return $this->belongsTo(Order::class); }
    public function menu()  { return $this->belongsTo(Menu::class); }
}
