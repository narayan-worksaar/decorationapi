<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeasurementDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('measurement_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('service_id')->unsigned()->nullable();
            $table->foreign('service_id')->references('id')->on('services')->onUpdate('cascade');
            $table->bigInteger('task_type_id')->unsigned()->nullable();
            $table->foreign('task_type_id')->references('id')->on('task_types')->onUpdate('cascade');
            $table->string('product')->nullable();
            $table->string('approx_SFT')->nullable();
            $table->string('notes')->nullable();
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
        Schema::dropIfExists('measurement_details');
    }
}
