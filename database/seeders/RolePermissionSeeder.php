<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Roles
        $roles = [
            'admin'    => \App\Models\Role::updateOrCreate(['slug' => 'admin'],    ['name' => 'Administrator', 'description' => 'Pengelola penuh seluruh sistem']),
            'owner'    => \App\Models\Role::updateOrCreate(['slug' => 'owner'],    ['name' => 'Owner',         'description' => 'Pemilik tenant atau kantin']),
            'staff'    => \App\Models\Role::updateOrCreate(['slug' => 'staff'],    ['name' => 'Staff',         'description' => 'Karyawan operasional kantin']),
            'customer' => \App\Models\Role::updateOrCreate(['slug' => 'customer'], ['name' => 'Customer',      'description' => 'Pembeli atau pelanggan']),
        ];

        // 2. Define Permissions (with simplified matrix style resources)
        $resources = ['Menu', 'Pesanan', 'Laporan', 'User', 'Tenant', 'Sistem'];
        $actions   = ['Read', 'Create', 'Update', 'Delete'];

        $permsList = [];
        foreach ($resources as $res) {
            foreach ($actions as $act) {
                $name = "{$act} {$res}";
                $slug = \Illuminate\Support\Str::slug($name);
                $permsList[$slug] = \App\Models\Permission::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name'     => $name,
                        'group_name'    => strtolower($res),
                        'resource' => $res,
                        'description' => "Hak akses untuk {$act} data {$res}"
                    ]
                );
            }
        }

        // 3. Assign Permissions to Roles
        // Admin: All
        $roles['admin']->permissions()->sync(array_column($permsList, 'id'));

        // Owner: Menu, Pesanan, Laporan
        $ownerPerms = \App\Models\Permission::whereIn('resource', ['Menu', 'Pesanan', 'Laporan'])->pluck('id');
        $roles['owner']->permissions()->sync($ownerPerms);

        // Staff: Read Menu, Read/Update Pesanan
        $staffPerms = \App\Models\Permission::where('resource', 'Menu')->where('slug', 'like', 'read-%')
            ->orWhere('resource', 'Pesanan')->whereIn('slug', ['read-pesanan', 'update-pesanan'])
            ->pluck('id');
        $roles['staff']->permissions()->sync($staffPerms);

        // 4. Map existing users to role_id
        foreach (\App\Models\User::all() as $user) {
            if (isset($roles[$user->role])) {
                $user->update(['role_id' => $roles[$user->role]->id]);
            }
        }
    }
}
