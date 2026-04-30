<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\ConfigVersion;
use App\Models\ActivityLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $settings = SystemSetting::where('is_deleted', 0)
            ->when($request->group, fn($q) => $q->where('group', $request->group))
            ->orderBy('group')->orderBy('key')
            ->get()
            ->groupBy('group');

        return $this->success($settings);
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings'         => 'required|array',
            'settings.*.key'   => 'required|string|exists:system_settings,key',
            'settings.*.value' => 'nullable',
        ]);

        $latestVersion = ConfigVersion::max('version') ?? 0;
        $username = $request->user()->username;
        $updated  = [];

        foreach ($request->settings as $item) {
            $old = SystemSetting::where('key', $item['key'])->value('value');
            SystemSetting::set($item['key'], $item['value']);

            // Track version
            ConfigVersion::create([
                'version'      => $latestVersion + 1,
                'changed_key'  => $item['key'],
                'old_value'    => $old,
                'new_value'    => $item['value'],
                'changed_by'   => $username,
                'company_code' => 'UNIV',
            ]);

            $updated[] = ['key' => $item['key'], 'old' => $old, 'new' => $item['value']];
        }

        ActivityLog::record('settings', "Admin update " . count($updated) . " setting: " . implode(', ', array_column($updated, 'key')));

        return $this->success($updated, 'Pengaturan berhasil disimpan');
    }

    public function versions(Request $request)
    {
        $versions = ConfigVersion::orderByDesc('version')
            ->orderByDesc('created_at')
            ->paginate(50);

        return $this->success($versions);
    }
}
