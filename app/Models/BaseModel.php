<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $guarded = ['id'];

    public function scopeActive($query)
    {
        return $query->where('status', 1)->where('is_deleted', 0);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', 0);
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->created_by   = auth()->user()?->username ?? 'system';
            $model->updated_by   = auth()->user()?->username ?? 'system';
            $model->company_code = $model->company_code ?? 'UNIV';
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->user()?->username ?? 'system';
        });
    }
}
