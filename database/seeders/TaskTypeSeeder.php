<?php

namespace Database\Seeders;

use App\Models\TaskType;
use Illuminate\Database\Seeder;

class TaskTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if records exist before creating
        $measurementExists = TaskType::where('task_name', 'Measurement')->exists();
        $installationExists = TaskType::where('task_name', 'Installation')->exists();

        if (!$measurementExists) {
            TaskType::create([
                'task_name' => 'Measurement',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!$installationExists) {
            TaskType::create([
                'task_name' => 'Installation',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
