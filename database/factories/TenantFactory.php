<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company() . ' ' . fake()->randomElement(['Kantin', 'Warung', 'Kedai', 'Cafe']);
        return [
            'user_id'      => User::factory()->owner(),
            'tenant_name'  => $name,
            'slug'         => Str::slug($name),
            'description'  => fake()->sentence(),
            'address'      => fake()->address(),
            'phone'        => '08' . fake()->numerify('#########'),
            'min_order'    => fake()->randomElement([0, 5000, 10000, 15000]),
            'is_open'      => true,
            'status'       => 1,
            'is_deleted'   => 0,
            'company_code' => 'UNIV',
            'created_by'   => 'factory',
            'updated_by'   => 'factory',
        ];
    }

    public function closed(): static  { return $this->state(['is_open' => false]); }
    public function inactive(): static { return $this->state(['status' => 0]); }
}
