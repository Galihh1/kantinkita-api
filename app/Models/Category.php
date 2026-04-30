<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends BaseModel
{
    use HasFactory;


    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function menus()  { return $this->hasMany(Menu::class)->active(); }
}
