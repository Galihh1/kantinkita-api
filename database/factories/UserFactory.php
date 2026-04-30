<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'          => fake()->name(),
            'username'      => fake()->unique()->userName(),
            'full_name'     => fake()->name(),
            'email'         => fake()->unique()->safeEmail(),
            'phone'         => '08' . fake()->numerify('#########'),
            'password'      => Hash::make('password'),
            'role'          => 'customer',
            'status'        => 1,
            'is_deleted'    => 0,
            'email_notif'   => true,
            'wa_notif'      => false,
            'company_code'  => 'UNIV',
            'created_by'    => 'factory',
            'updated_by'    => 'factory',
        ];
    }

    public function admin(): static    { return $this->state(['role' => 'admin']); }
    public function owner(): static    { return $this->state(['role' => 'owner']); }
    public function staff(): static    { return $this->state(['role' => 'staff']); }
    public function customer(): static { return $this->state(['role' => 'customer']); }
    public function inactive(): static { return $this->state(['status' => 0]); }
}
