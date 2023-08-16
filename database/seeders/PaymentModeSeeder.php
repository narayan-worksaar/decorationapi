<?php

namespace Database\Seeders;

use App\Models\PaymentMode;
use Illuminate\Database\Seeder;

class PaymentModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /*
        PaymentMode::create([
            [
                'payment_method_name' => 'Customer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'payment_method_name' => 'Dealer',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
        */

        // Check if records exist before creating
        $customerExists = PaymentMode::where('payment_method_name', 'Customer')->exists();
        $dealerExists = PaymentMode::where('payment_method_name', 'Dealer')->exists();

        if (!$customerExists) {
            PaymentMode::create([
                'payment_method_name' => 'Customer',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!$dealerExists) {
            PaymentMode::create([
                'payment_method_name' => 'Dealer',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
