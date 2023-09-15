<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentCollectedToServiceUpdateByAgentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_update_by_agent', function (Blueprint $table) {
            $table->foreign('payment_collected')->references('id')->on('yes_no')->onUpdate('cascade');
            $table->bigInteger('payment_collected')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_update_by_agent', function (Blueprint $table) {
            $table->dropForeign(['payment_collected']); 
            $table->dropColumn('payment_collected');
        });
    }
}
