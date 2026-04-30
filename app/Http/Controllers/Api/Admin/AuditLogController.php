<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use League\Csv\Writer;

class AuditLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->user_id,    fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->action,     fn($q) => $q->where('action', $request->action))
            ->when($request->search,     fn($q) => $q->where('description', 'like', "%{$request->search}%"))
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date,   fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->orderByDesc('created_at')
            ->paginate(50);

        return $this->success($logs);
    }

    public function export(Request $request)
    {
        $request->validate(['start_date' => 'nullable|date', 'end_date' => 'nullable|date']);

        $logs = ActivityLog::with('user')
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date,   fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->orderByDesc('created_at')
            ->limit(5000)->get();

        $csv = Writer::createFromString('');
        $csv->insertOne(['ID', 'User', 'Action', 'Method', 'URL', 'Status', 'Description', 'IP Address', 'Timestamp']);

        foreach ($logs as $log) {
            $csv->insertOne([
                $log->id,
                $log->user?->full_name ?? 'System',
                $log->action,
                $log->method,
                $log->url,
                $log->status_code,
                $log->description,
                $log->ip_address,
                $log->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        $filename = "audit_logs_" . now()->format('Ymd_His') . ".csv";
        return response((string) $csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }
}
