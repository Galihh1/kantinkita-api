<?php
namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id'    => Tenant::factory(),
            'category_id'  => Category::factory(),
            'name'         => fake()->randomElement(['Nasi Goreng', 'Mie Goreng', 'Ayam Bakar', 'Es Teh', 'Juice Alpukat', 'Gado-Gado', 'Soto Ayam']),
            'description'  => fake()->sentence(),
            'price'        => fake()->numberBetween(5000, 75000),
            'is_available' => true,
            'status'       => 1,
            'is_deleted'   => 0,
            'company_code' => 'UNIV',
            'created_by'   => 'factory',
            'updated_by'   => 'factory',
        ];
    }

    public function unavailable(): static { return $this->state(['is_available' => false]); }
}
