<?php
namespace Tests\Unit;

use App\Models\Order;
use App\Models\SystemSetting;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderService();

        // Seed minimal system settings
        SystemSetting::insert([
            ['key' => 'fee_type',  'value' => 'percentage', 'type' => 'string', 'group' => 'payment', 'label' => 'Fee Type',  'company_code' => 'UNIV', 'created_by' => 'test', 'updated_by' => 'test'],
            ['key' => 'fee_value', 'value' => '2',           'type' => 'float',  'group' => 'payment', 'label' => 'Fee Value', 'company_code' => 'UNIV', 'created_by' => 'test', 'updated_by' => 'test'],
        ]);
    }

    public function test_calculate_fee_percentage(): void
    {
        $fee = $this->service->calculateFee(100000);
        $this->assertEquals(2000, $fee);
    }

    public function test_calculate_fee_fixed(): void
    {
        SystemSetting::where('key', 'fee_type')->update(['value' => 'fixed']);
        SystemSetting::where('key', 'fee_value')->update(['value' => '5000']);

        $fee = $this->service->calculateFee(100000);
        $this->assertEquals(5000, $fee);
    }

    public function test_generate_order_number(): void
    {
        $orderNumber = $this->service->generateOrderNumber();

        $this->assertStringContainsString('INV/', $orderNumber);
        $this->assertStringContainsString(date('Ymd'), $orderNumber);
        $this->assertMatchesRegularExpression('/INV\/\d{8}\/\d{4}/', $orderNumber);
    }

    public function test_valid_status_transition(): void
    {
        $valid = $this->service->isValidTransition(
            Order::STATUS_PENDING,
            Order::STATUS_EXPIRED
        );
        $this->assertTrue($valid);
    }

    public function test_invalid_status_transition(): void
    {
        $valid = $this->service->isValidTransition(
            Order::STATUS_PENDING,
            Order::STATUS_COMPLETED
        );
        $this->assertFalse($valid);
    }

    public function test_paid_to_processing_valid(): void
    {
        $valid = $this->service->isValidTransition(
            Order::STATUS_PAID,
            Order::STATUS_PROCESSING
        );
        $this->assertTrue($valid);
    }

    public function test_processing_to_completed_valid(): void
    {
        $valid = $this->service->isValidTransition(
            Order::STATUS_PROCESSING,
            Order::STATUS_COMPLETED
        );
        $this->assertTrue($valid);
    }
}
