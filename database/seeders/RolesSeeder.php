<?php

namespace Database\Seeders;

use App\Models\Roles;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if records exist before creating
        $adminExists = Roles::where('name', 'admin')->exists();
        $userExists = Roles::where('name', 'user')->exists();
        $agentExists = Roles::where('name', 'agent')->exists();
        $dealerExists = Roles::where('name', 'dealer')->exists();
        $dealer_employeeExists = Roles::where('name', 'dealer_employee')->exists();

        if (!$adminExists) {
            Roles::create([
                'name' => 'admin',
                'display_name' => 'Administrator',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if (!$userExists) {
            Roles::create([
                'name' => 'user',
                'display_name' => 'Normal User',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!$agentExists) {
            Roles::create([
                'name' => 'agent',
                'display_name' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!$dealerExists) {
            Roles::create([
                'name' => 'dealer',
                'display_name' => 'Dealer',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if (!$dealer_employeeExists) {
            Roles::create([
                'name' => 'dealer_employee',
                'display_name' => 'Dealer Employee',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        
    }
}
