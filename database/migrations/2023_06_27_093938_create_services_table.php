<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('service_code')->nullable();
            $table->string('client_name');
            $table->string('client_email_address')->nullable();
            $table->string('client_mobile_number')->nullable();
            $table->string('client_alternate_mobile_number')->nullable();

            $table->string('date_time')->nullable();
            $table->bigInteger('task_type_id')->unsigned()->nullable();
            $table->foreign('task_type_id')->references('id')->on('task_types')->onUpdate('cascade');
            $table->text('measurement')->nullable();
            $table->text('descriptions')->nullable();
            $table->bigInteger('action_type_id')->unsigned()->nullable();
            $table->foreign('action_type_id')->references('id')->on('action_types')->onUpdate('cascade');
            
            $table->bigInteger('assigned_agent_id')->unsigned()->nullable();
            $table->foreign('assigned_agent_id')->references('id')->on('users')->onUpdate('cascade');

            $table->bigInteger('dealer_id')->unsigned()->nullable();
            $table->foreign('dealer_id')->references('id')->on('users')->onUpdate('cascade');
            $table->string('service_charge')->nullable();
            $table->string('slug')->nullable();
            $table->string('meta_data')->nullable();
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
        Schema::dropIfExists('services');
    }
}
