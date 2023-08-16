<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentModeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_mode', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method_name')->nullable();
            $table->string('remarks')->nullable();
            $table->string('status')->enum("active","inactive")->default("active");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_mode');
    }
}
