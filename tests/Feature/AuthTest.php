<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::insert([
            ['key' => 'fee_type',  'value' => 'percentage', 'type' => 'string', 'group' => 'payment', 'label' => 'Fee',        'company_code' => 'UNIV', 'created_by' => 'test', 'updated_by' => 'test'],
            ['key' => 'fee_value', 'value' => '5',          'type' => 'float',  'group' => 'payment', 'label' => 'Fee Value',  'company_code' => 'UNIV', 'created_by' => 'test', 'updated_by' => 'test'],
            ['key' => 'payment_timeout', 'value' => '30',   'type' => 'integer','group' => 'payment', 'label' => 'Timeout',    'company_code' => 'UNIV', 'created_by' => 'test', 'updated_by' => 'test'],
        ]);
    }

    public function test_register_success(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'full_name'             => 'Test User',
            'username'              => 'testuser123',
            'email'                 => 'test@example.com',
            'phone'                 => '081234567890',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('status', true)
                 ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_register_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'full_name'             => 'Test',
            'username'              => 'newuser',
            'email'                 => 'dup@example.com',
            'phone'                 => '081234567890',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonPath('status', false);
    }

    public function test_login_success(): void
    {
        User::factory()->create(['email' => 'login@example.com', 'password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', true)
                 ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_login_wrong_password(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => bcrypt('correct')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)->assertJsonPath('status', false);
    }

    public function test_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)->assertJsonPath('status', true);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_returns_current_user(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
                 ->assertJsonPath('data.email', $user->email);
    }
}
