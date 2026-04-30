<?php
namespace Database\Factories;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $total      = fake()->numberBetween(15000, 200000);
        $fee        = round($total * 0.05);
        $grand      = $total + $fee;

        return [
            'order_number' => 'INV/' . now()->format('Ymd') . '/' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'user_id'      => User::factory()->customer(),
            'tenant_id'    => Tenant::factory(),
            'status'       => Order::STATUS_PENDING,
            'total_amount' => $total,
            'service_fee'  => $fee,
            'grand_total'  => $grand,
            'expires_at'   => now()->addMinutes(30),
            'company_code' => 'UNIV',
            'created_by'   => 'factory',
            'updated_by'   => 'factory',
        ];
    }

    public function paid(): static      { return $this->state(['status' => Order::STATUS_PAID]); }
    public function completed(): static { return $this->state(['status' => Order::STATUS_COMPLETED]); }
    public function expired(): static   { return $this->state(['status' => Order::STATUS_EXPIRED, 'expires_at' => now()->subMinutes(5)]); }
    public function cart(): static      { return $this->state(['status' => 'cart', 'order_number' => 'CART-' . fake()->numerify('####')]); }
}
