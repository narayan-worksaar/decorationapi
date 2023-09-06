<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteImageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_image', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('service_updated_by_agent_id')->unsigned()->nullable();
            $table->foreign('service_updated_by_agent_id')->references('id')->on('service_update_by_agent')->onUpdate('cascade');
            $table->string('site_image_file')->nullable();
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
        Schema::dropIfExists('site_image');
    }
}
