<?php
namespace Tests\Unit;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportService();

        $owner        = User::factory()->owner()->create();
        $this->tenant = Tenant::factory()->create(['user_id' => $owner->id]);
    }

    public function test_get_sales_report_empty(): void
    {
        $report = $this->service->getSalesReport(
            $this->tenant->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals(0, $report['total_revenue']);
        $this->assertEquals(0, $report['total_orders']);
        $this->assertEquals(0, $report['avg_order']);
        $this->assertEmpty($report['top_menus']);
        $this->assertEmpty($report['daily_chart']);
    }

    public function test_get_sales_report_with_data(): void
    {
        $user = User::factory()->customer()->create();

        Order::factory()->completed()->create([
            'tenant_id'   => $this->tenant->id,
            'user_id'     => $user->id,
            'grand_total' => 50000,
            'created_at'  => now()->subDays(2),
        ]);

        Order::factory()->completed()->create([
            'tenant_id'   => $this->tenant->id,
            'user_id'     => $user->id,
            'grand_total' => 75000,
            'created_at'  => now()->subDays(1),
        ]);

        $report = $this->service->getSalesReport(
            $this->tenant->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals(125000, $report['total_revenue']);
        $this->assertEquals(2, $report['total_orders']);
        $this->assertEquals(62500, $report['avg_order']);
    }

    public function test_excludes_non_completed_orders(): void
    {
        $user = User::factory()->customer()->create();

        // Pending order - should not be counted
        Order::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'user_id'     => $user->id,
            'grand_total' => 100000,
        ]);

        $report = $this->service->getSalesReport(
            $this->tenant->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals(0, $report['total_revenue']);
        $this->assertEquals(0, $report['total_orders']);
    }
}
