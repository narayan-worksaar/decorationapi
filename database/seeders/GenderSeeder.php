<?php

namespace Database\Seeders;

use App\Models\Gender;
use Illuminate\Database\Seeder;

class GenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if records exist before creating
        $maleExists = Gender::where('name', 'Male')->exists();
        $femaleExists = Gender::where('name', 'Female')->exists();

        if (!$maleExists) {
            Gender::create([
                'name' => 'Male',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!$femaleExists) {
            Gender::create([
                'name' => 'Female',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
