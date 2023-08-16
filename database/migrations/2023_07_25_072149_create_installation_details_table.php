<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstallationDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('installation_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('service_id')->unsigned()->nullable();
            $table->foreign('service_id')->references('id')->on('services')->onUpdate('cascade');
            $table->bigInteger('task_type_id')->unsigned()->nullable();
            $table->foreign('task_type_id')->references('id')->on('task_types')->onUpdate('cascade');
            $table->string('no_of_rolls_box_sqft')->nullable();
            $table->string('no_of_surface')->nullable();
            $table->string('surface_details')->nullable();
            $table->string('surface_condition_status')->nullable();
            $table->string('material_code')->nullable();
            $table->string('type_of_material')->nullable();
            $table->string('remarks')->nullable();
            $table->bigInteger('created_by_user_id')->unsigned()->nullable();
            $table->foreign('created_by_user_id')->references('id')->on('users')->onUpdate('cascade');
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
        Schema::dropIfExists('installation_details');
    }
}
