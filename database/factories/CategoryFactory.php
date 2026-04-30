<?php
namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id'    => Tenant::factory(),
            'name'         => fake()->randomElement(['Nasi & Lauk', 'Minuman', 'Snack', 'Dessert', 'Mie & Pasta']),
            'status'       => 1,
            'is_deleted'   => 0,
            'company_code' => 'UNIV',
            'created_by'   => 'factory',
            'updated_by'   => 'factory',
        ];
    }
}
