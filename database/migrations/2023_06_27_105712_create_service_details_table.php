<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_details', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_time')->nullable();
            $table->text('measurement')->nullable();
            $table->text('descriptions')->nullable();
            $table->string('quotation_amount')->nullable();
            $table->bigInteger('service_id')->unsigned();
            $table->foreign('service_id')->references('id')->on('services')->onUpdate('cascade');
            $table->bigInteger('client_id')->unsigned()->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->onUpdate('cascade');
            $table->bigInteger('dealer_id')->unsigned()->nullable();
            $table->foreign('dealer_id')->references('id')->on('users')->onUpdate('cascade');
            $table->bigInteger('service_status_id')->unsigned()->nullable();
            $table->foreign('service_status_id')->references('id')->on('service_status')->onUpdate('cascade');
            // $table->string('status')->enum("measurement","installation","rectification","pending")->default("measurement");
            $table->string('service_type')->nullable();
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
        Schema::dropIfExists('service_details');
    }
}
