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
            // Ambil company_code dari user yang sedang login, bukan hardcode 'UNIV'
            $model->company_code = $model->company_code
                ?? auth()->user()?->company_code
                ?? 'UNIV';
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->user()?->username ?? 'system';
        });
    }

    /**
     * Force HTTPS untuk URL storage agar tidak kena Mixed Content di production.
     * Cukup dipanggil dari getPhotoUrlAttribute() setiap model.
     */
    protected function storageUrl(?string $path): ?string
    {
        if (!$path) return null;
        $url = filter_var($path, FILTER_VALIDATE_URL) ? $path : asset('storage/' . $path);
        // Force HTTPS di production (Railway menghasilkan http:// dari asset())
        if (app()->environment('production') || str_starts_with(config('app.url', ''), 'https')) {
            $url = str_replace('http://', 'https://', $url);
        }
        return $url;
    }
}
