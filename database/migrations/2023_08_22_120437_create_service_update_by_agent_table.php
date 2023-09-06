<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceUpdateByAgentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_update_by_agent', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('service_id')->unsigned()->nullable();
            $table->foreign('service_id')->references('id')->on('services')->onUpdate('cascade');
            $table->string('form')->nullable();
            $table->text('remarks')->nullable();
            $table->bigInteger('status')->unsigned()->nullable();
            $table->foreign('status')->references('id')->on('status')->onUpdate('cascade');
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onUpdate('cascade');
            $table->date('created_date')->nullable();
            $table->time('created_time')->nullable();
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
        Schema::dropIfExists('service_update_by_agent');
    }
}
