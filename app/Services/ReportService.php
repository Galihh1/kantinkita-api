<?php
namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use League\Csv\Writer;

class ReportService
{
    public function getSalesReport(int $tenantId, string $startDate, string $endDate): array
    {
        $orders = Order::with(['items', 'payment', 'user'])
            ->where('tenant_id', $tenantId)
            ->where('status', Order::STATUS_COMPLETED)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->get();

        $totalRevenue = $orders->sum('grand_total');
        $totalOrders  = $orders->count();

        $topMenus = OrderItem::whereIn('order_id', $orders->pluck('id'))
            ->selectRaw('menu_name, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
            ->groupBy('menu_name')->orderByDesc('total_qty')->limit(10)->get();

        $dailyChart = $orders->groupBy(fn($o) => Carbon::parse($o->created_at)->format('Y-m-d'))
            ->map(fn($dayOrders, $date) => [
                'date'    => $date,
                'revenue' => $dayOrders->sum('grand_total'),
                'orders'  => $dayOrders->count(),
            ])->values();

        $paymentMethods = $orders->groupBy(fn($o) => $o->payment?->payment_type ?? 'unknown')
            ->map(fn($group, $type) => [
                'type'  => $type,
                'count' => $group->count(),
                'total' => $group->sum('grand_total'),
            ])->values();

        return [
            'total_revenue'   => $totalRevenue,
            'total_orders'    => $totalOrders,
            'avg_order'       => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
            'top_menus'       => $topMenus,
            'daily_chart'     => $dailyChart,
            'payment_methods' => $paymentMethods,
            'orders'          => $orders,
        ];
    }

    public function exportPdf(int $tenantId, string $startDate, string $endDate)
    {
        $report = $this->getSalesReport($tenantId, $startDate, $endDate);
        $tenant = \App\Models\Tenant::find($tenantId);

        return Pdf::loadView('reports.sales-pdf', compact('report', 'tenant', 'startDate', 'endDate'))
                  ->setPaper('a4', 'portrait');
    }

    public function exportCsv(int $tenantId, string $startDate, string $endDate): string
    {
        $report = $this->getSalesReport($tenantId, $startDate, $endDate);
        $csv    = Writer::createFromString('');

        $csv->insertOne(['No', 'Order Number', 'Customer', 'Tanggal', 'Items', 'Total', 'Fee', 'Grand Total', 'Payment Type', 'Status']);

        foreach ($report['orders'] as $i => $order) {
            $items = $order->items->map(fn($item) => "{$item->menu_name} x{$item->quantity}")->implode(', ');
            $csv->insertOne([
                $i + 1, $order->order_number, $order->user?->full_name ?? 'Guest',
                Carbon::parse($order->created_at)->format('d/m/Y H:i'),
                $items, $order->total_amount, $order->service_fee, $order->grand_total,
                $order->payment?->payment_type ?? '-', $order->status,
            ]);
        }

        return (string) $csv;
    }

    public function getAggregate(string $startDate, string $endDate): array
    {
        $orders = Order::with('tenant')
            ->where('status', Order::STATUS_COMPLETED)
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->get();

        $byTenant = $orders->groupBy('tenant_id')->map(fn($group) => [
            'tenant_name'  => $group->first()->tenant?->tenant_name,
            'total_orders' => $group->count(),
            'total_revenue'=> $group->sum('grand_total'),
        ])->values();

        return [
            'total_revenue' => $orders->sum('grand_total'),
            'total_orders'  => $orders->count(),
            'by_tenant'     => $byTenant,
        ];
    }
}
