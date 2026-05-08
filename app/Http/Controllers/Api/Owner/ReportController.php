<?php
namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(private ReportService $reportService) {}

    public function index(Request $request)
    {
        $request->validate(['start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date']);

        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);
        $tenantId = $tenant->id;
        $report   = $this->reportService->getSalesReport($tenantId, $request->start_date, $request->end_date);

        $orders = \App\Models\Order::with(['items'])
            ->where('tenant_id', $tenantId)
            ->where('status', \App\Models\Order::STATUS_COMPLETED)
            ->whereBetween('created_at', [
                \Carbon\Carbon::parse($request->start_date)->startOfDay(),
                \Carbon\Carbon::parse($request->end_date)->endOfDay(),
            ])
            ->paginate(15);

        $summary = $report;
        unset($summary['orders']);

        return response()->json([
            'status'  => true,
            'message' => 'Berhasil',
            'data'    => $orders,
            'summary' => $summary,
        ], 200);
    }

    public function exportPdf(Request $request)
    {
        $request->validate(['start_date' => 'required|date', 'end_date' => 'required|date']);

        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);
        $tenantId = $tenant->id;
        $pdf      = $this->reportService->exportPdf($tenantId, $request->start_date, $request->end_date);

        return $pdf->download("laporan-{$request->start_date}-{$request->end_date}.pdf");
    }

    public function exportCsv(Request $request)
    {
        $request->validate(['start_date' => 'required|date', 'end_date' => 'required|date']);

        $tenant = $request->user()->tenant;
        if (!$tenant) return $this->error('Owner belum memiliki tenant.', 403);
        $tenantId = $tenant->id;
        $csv      = $this->reportService->exportCsv($tenantId, $request->start_date, $request->end_date);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=laporan-{$request->start_date}-{$request->end_date}.csv",
        ]);
    }

    public function aggregate(Request $request)
    {
        $request->validate(['start_date' => 'required|date', 'end_date' => 'required|date']);
        $data = $this->reportService->getAggregate($request->start_date, $request->end_date);

        return $this->success($data);
    }
}
