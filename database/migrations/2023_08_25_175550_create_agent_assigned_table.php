<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentAssignedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_assigned', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('service_id')->unsigned()->nullable();
            $table->foreign('service_id')->references('id')->on('services')->onUpdate('cascade');
            $table->bigInteger('assigned_by')->unsigned()->nullable();
            $table->foreign('assigned_by')->references('id')->on('users')->onUpdate('cascade');
            $table->bigInteger('agent')->unsigned()->nullable();
            $table->foreign('agent')->references('id')->on('users')->onUpdate('cascade');
            $table->date('assigned_date')->nullable();
            $table->time('assigned_time')->nullable();
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
        Schema::dropIfExists('agent_assigned');
    }
}
