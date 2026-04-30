<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Subscription;
use App\Models\SystemSetting;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. USERS ──────────────────────────────────────────
        $admin = User::create([
            'name'         => 'Administrator KantinKita',
            'username'     => 'admin',
            'full_name'    => 'Administrator KantinKita',
            'email'        => 'admin@kantinkita.com',
            'phone'        => '081200000000',
            'password'     => Hash::make('password123'),
            'role'         => 'admin',
            'status'       => 1,
            'email_notif'  => true,
            'company_code' => 'UNIV',
            'created_by'   => 'system',
            'updated_by'   => 'system',
        ]);

        $owner1 = User::create([
            'name'         => 'Budi Santoso',
            'username'     => 'owner1',
            'full_name'    => 'Budi Santoso',
            'email'        => 'owner1@kantinkita.com',
            'phone'        => '081211111111',
            'password'     => Hash::make('password123'),
            'role'         => 'owner',
            'status'       => 1,
            'email_notif'  => true,
            'wa_notif'     => true,
            'company_code' => 'UNIV',
            'created_by'   => 'system',
            'updated_by'   => 'system',
        ]);

        $owner2 = User::create([
            'name'         => 'Siti Rahayu',
            'username'     => 'owner2',
            'full_name'    => 'Siti Rahayu',
            'email'        => 'owner2@kantinkita.com',
            'phone'        => '081222222222',
            'password'     => Hash::make('password123'),
            'role'         => 'owner',
            'status'       => 1,
            'email_notif'  => true,
            'company_code' => 'UNIV',
            'created_by'   => 'system',
            'updated_by'   => 'system',
        ]);

        $staff1 = User::create([
            'name'         => 'Ahmad Staff',
            'username'     => 'staff1',
            'full_name'    => 'Ahmad Staff',
            'email'        => 'staff1@kantinkita.com',
            'phone'        => '081233333333',
            'password'     => Hash::make('password123'),
            'role'         => 'staff',
            'status'       => 1,
            'company_code' => 'UNIV',
            'created_by'   => 'system',
            'updated_by'   => 'system',
        ]);

        $customer1 = User::create([
            'name'         => 'Dewi Mahasiswi',
            'username'     => 'customer1',
            'full_name'    => 'Dewi Mahasiswi',
            'email'        => 'customer1@kantinkita.com',
            'phone'        => '081244444444',
            'password'     => Hash::make('password123'),
            'role'         => 'customer',
            'status'       => 1,
            'email_notif'  => true,
            'wa_notif'     => true,
            'company_code' => 'UNIV',
            'created_by'   => 'system',
            'updated_by'   => 'system',
        ]);


        // ─── 2. TENANTS ────────────────────────────────────────
        $tenant1 = Tenant::create([
            'user_id'      => $owner1->id,
            'tenant_name'  => 'Warung Makan Barokah',
            'slug'         => 'warung-makan-barokah',
            'description'  => 'Masakan rumahan enak dan murah, cocok untuk mahasiswa.',
            'address'      => 'Gedung A Lt.1, Kampus UNIV',
            'phone'        => '081211110001',
            'is_open'      => true,
            'min_order'    => 10000,
            'status'       => 1,
            'company_code' => 'UNIV',
            'created_by'   => 'system', 'updated_by' => 'system',
        ]);

        $tenant2 = Tenant::create([
            'user_id'      => $owner2->id,
            'tenant_name'  => 'Kantin Sehat Ibu Siti',
            'slug'         => 'kantin-sehat-ibu-siti',
            'description'  => 'Menu sehat dan bergizi untuk sivitas akademika.',
            'address'      => 'Gedung B Lt.2, Kampus UNIV',
            'phone'        => '081222220002',
            'is_open'      => true,
            'min_order'    => 15000,
            'status'       => 1,
            'company_code' => 'UNIV',
            'created_by'   => 'system', 'updated_by' => 'system',
        ]);

        // Attach staff to tenant1
        $tenant1->staff()->attach($staff1->id);

        // ─── 3. SUBSCRIPTIONS ──────────────────────────────────
        Subscription::create([
            'tenant_id'      => $tenant1->id,
            'plan'           => 'professional',
            'billing_start'  => now()->startOfMonth(),
            'billing_end'    => now()->addMonths(1)->endOfMonth(),
            'amount'         => 299000,
            'billing_status' => 'active',
            'company_code'   => 'UNIV',
            'created_by'     => 'system', 'updated_by' => 'system',
        ]);

        Subscription::create([
            'tenant_id'      => $tenant2->id,
            'plan'           => 'starter',
            'billing_start'  => now()->startOfMonth(),
            'billing_end'    => now()->addMonths(1)->endOfMonth(),
            'amount'         => 99000,
            'billing_status' => 'active',
            'company_code'   => 'UNIV',
            'created_by'     => 'system', 'updated_by' => 'system',
        ]);

        // ─── 4. CATEGORIES & MENUS ─────────────────────────────
        $categories1 = ['Nasi & Lauk', 'Mie & Pasta', 'Minuman', 'Snack', 'Dessert'];
        foreach ($categories1 as $catName) {
            $cat = Category::create([
                'tenant_id' => $tenant1->id, 'name' => $catName,
                'company_code' => 'UNIV', 'created_by' => 'system', 'updated_by' => 'system',
            ]);

            $menus = match ($catName) {
                'Nasi & Lauk' => [
                    ['Nasi Ayam Goreng', 15000], ['Nasi Rendang', 18000],
                    ['Nasi Capcay', 13000], ['Nasi Telur Balado', 12000],
                ],
                'Mie & Pasta' => [
                    ['Mie Goreng Spesial', 13000], ['Mie Rebus', 12000], ['Kwetiau Goreng', 15000],
                ],
                'Minuman' => [
                    ['Es Teh Manis', 5000], ['Es Jeruk', 7000], ['Air Mineral', 4000], ['Jus Alpukat', 12000],
                ],
                'Snack' => [
                    ['Gorengan Mix', 8000], ['Tempe Mendoan', 5000],
                ],
                'Dessert' => [
                    ['Puding Coklat', 8000], ['Es Cendol', 7000],
                ],
                default => [],
            };

            foreach ($menus as [$menuName, $price]) {
                Menu::create([
                    'tenant_id' => $tenant1->id, 'category_id' => $cat->id,
                    'name' => $menuName, 'price' => $price,
                    'is_available' => true, 'status' => 1,
                    'company_code' => 'UNIV', 'created_by' => 'system', 'updated_by' => 'system',
                ]);
            }
        }

        $categories2 = ['Makanan Sehat', 'Jus & Smoothie', 'Minuman Sehat'];
        foreach ($categories2 as $catName) {
            $cat = Category::create([
                'tenant_id' => $tenant2->id, 'name' => $catName,
                'company_code' => 'UNIV', 'created_by' => 'system', 'updated_by' => 'system',
            ]);

            $menus = match ($catName) {
                'Makanan Sehat' => [
                    ['Salad Buah', 15000], ['Nasi Merah + Ayam Bakar', 22000], ['Gado-Gado', 18000],
                ],
                'Jus & Smoothie' => [
                    ['Jus Alpukat', 15000], ['Smoothie Mangga', 18000], ['Jus Semangka', 12000],
                ],
                'Minuman Sehat' => [
                    ['Air Kelapa Muda', 10000], ['Teh Hijau', 8000], ['Infused Water', 7000],
                ],
                default => [],
            };

            foreach ($menus as [$menuName, $price]) {
                Menu::create([
                    'tenant_id' => $tenant2->id, 'category_id' => $cat->id,
                    'name' => $menuName, 'price' => $price,
                    'is_available' => true, 'status' => 1,
                    'company_code' => 'UNIV', 'created_by' => 'system', 'updated_by' => 'system',
                ]);
            }
        }

        // ─── 5. SYSTEM SETTINGS ────────────────────────────────
        $settings = [
            ['key' => 'app_name',           'value' => 'KantinKita',          'type' => 'string',  'group' => 'general',  'label' => 'Nama Aplikasi'],
            ['key' => 'app_logo',           'value' => null,                  'type' => 'string',  'group' => 'general',  'label' => 'Logo Aplikasi'],
            ['key' => 'fee_type',           'value' => 'percentage',          'type' => 'string',  'group' => 'payment',  'label' => 'Tipe Fee (percentage/fixed)'],
            ['key' => 'fee_value',          'value' => '5',                   'type' => 'float',   'group' => 'payment',  'label' => 'Nilai Fee'],
            ['key' => 'fee_label',          'value' => 'Biaya Layanan',       'type' => 'string',  'group' => 'payment',  'label' => 'Label Fee'],
            ['key' => 'payment_timeout',    'value' => '30',                  'type' => 'integer', 'group' => 'payment',  'label' => 'Timeout Pembayaran (menit)'],
            ['key' => 'trial_days',         'value' => '14',                  'type' => 'integer', 'group' => 'subscription', 'label' => 'Masa Trial (hari)'],
            ['key' => 'price_starter',      'value' => '99000',               'type' => 'integer', 'group' => 'subscription', 'label' => 'Harga Paket Starter'],
            ['key' => 'price_professional', 'value' => '299000',              'type' => 'integer', 'group' => 'subscription', 'label' => 'Harga Paket Professional'],
            ['key' => 'price_enterprise',   'value' => '799000',              'type' => 'integer', 'group' => 'subscription', 'label' => 'Harga Paket Enterprise'],
            ['key' => 'notif_order_created',   'value' => '1', 'type' => 'boolean', 'group' => 'notification', 'label' => 'Notif Order Dibuat'],
            ['key' => 'notif_order_paid',      'value' => '1', 'type' => 'boolean', 'group' => 'notification', 'label' => 'Notif Order Dibayar'],
            ['key' => 'notif_order_processing','value' => '1', 'type' => 'boolean', 'group' => 'notification', 'label' => 'Notif Order Diproses'],
            ['key' => 'notif_order_completed', 'value' => '1', 'type' => 'boolean', 'group' => 'notification', 'label' => 'Notif Order Selesai'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::create(array_merge($setting, ['company_code' => 'UNIV', 'created_by' => 'system', 'updated_by' => 'system']));
        }

        $this->command->info('✅ KantinKita database seeded successfully!');
        $this->command->table(['Role', 'Email', 'Password'], [
            ['Admin',     'admin@kantinkita.com',    'password123'],
            ['Owner 1',   'owner1@kantinkita.com',   'password123'],
            ['Owner 2',   'owner2@kantinkita.com',   'password123'],
            ['Staff',     'staff1@kantinkita.com',   'password123'],
            ['Customer',  'customer1@kantinkita.com','password123'],
        ]);
    }
}
