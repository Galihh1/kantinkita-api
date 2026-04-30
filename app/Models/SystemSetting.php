<?php
namespace App\Models;

class SystemSetting extends BaseModel
{
    protected $casts = ['options' => 'array'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) return $default;

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'float'   => (float) $setting->value,
            'boolean' => (bool) $setting->value,
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    public static function set(string $key, mixed $value): void
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) return;

        $oldValue = $setting->value;
        $setting->update(['value' => (string) $value]);

        ConfigVersion::create([
            'version'      => (ConfigVersion::max('version') ?? 0) + 1,
            'changed_key'  => $key,
            'old_value'    => $oldValue,
            'new_value'    => (string) $value,
            'changed_by'   => auth()->user()?->username ?? 'system',
            'company_code' => 'UNIV',
        ]);
    }
}
