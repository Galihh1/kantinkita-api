<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\ActivityLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $logs = ErrorLog::with('user')
            ->when($request->level,           fn($q) => $q->where('level', $request->level))
            ->when($request->resolved_status, fn($q) => $q->where('resolved_status', $request->resolved_status))
            ->when($request->start_date,      fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date,        fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->orderByDesc('created_at')
            ->paginate(50);

        return $this->success($logs);
    }

    public function resolve(Request $request, int $id)
    {
        $log = ErrorLog::findOrFail($id);

        if ($log->resolved_status === 'resolved') {
            return $this->error('Error log sudah resolved.', 422);
        }

        $log->update([
            'resolved_status' => 'resolved',
            'resolved_by'     => $request->user()->username,
            'resolved_at'     => now(),
        ]);

        ActivityLog::record('update', "Admin resolve error log #{$id}: " . substr($log->message, 0, 100));
        return $this->success($log->fresh(), 'Error log berhasil di-resolve');
    }

    public function stats()
    {
        return $this->success([
            'open'        => ErrorLog::where('resolved_status', 'open')->count(),
            'in_progress' => ErrorLog::where('resolved_status', 'in_progress')->count(),
            'resolved'    => ErrorLog::where('resolved_status', 'resolved')->count(),
            'by_level'    => ErrorLog::selectRaw('level, count(*) as total')->groupBy('level')->pluck('total', 'level'),
        ]);
    }
}
