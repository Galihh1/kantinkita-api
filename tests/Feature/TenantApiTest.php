<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Category;
use App\Models\Menu;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $owner        = User::factory()->owner()->create();
        $this->tenant = Tenant::factory()->create(['user_id' => $owner->id, 'status' => 1]);

        SystemSetting::insert([
            ['key' => 'fee_type',  'value' => 'percentage', 'type' => 'string', 'group' => 'payment', 'label' => 'Fee Type', 'company_code' => 'UNIV', 'created_by' => 'test', 'updated_by' => 'test'],
            ['key' => 'fee_value', 'value' => '5',          'type' => 'float',  'group' => 'payment', 'label' => 'Fee Val',  'company_code' => 'UNIV', 'created_by' => 'test', 'updated_by' => 'test'],
        ]);
    }

    public function test_list_tenants_public(): void
    {
        $response = $this->getJson('/api/v1/tenants');

        $response->assertStatus(200)
                 ->assertJsonPath('status', true)
                 ->assertJsonStructure(['data' => ['data']]);
    }

    public function test_show_tenant(): void
    {
        $response = $this->getJson("/api/v1/tenants/{$this->tenant->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $this->tenant->id)
                 ->assertJsonPath('data.tenant_name', $this->tenant->tenant_name);
    }

    public function test_tenant_menus(): void
    {
        $cat  = Category::factory()->create(['tenant_id' => $this->tenant->id]);
        Menu::factory(3)->create(['tenant_id' => $this->tenant->id, 'category_id' => $cat->id, 'is_available' => 1]);

        $response = $this->getJson("/api/v1/tenants/{$this->tenant->id}/menus");

        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_inactive_tenant_not_listed(): void
    {
        $owner          = User::factory()->owner()->create();
        $inactiveTenant = Tenant::factory()->create(['user_id' => $owner->id, 'status' => 0]);

        $response = $this->getJson('/api/v1/tenants');

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertNotContains($inactiveTenant->id, $ids);
    }
}
