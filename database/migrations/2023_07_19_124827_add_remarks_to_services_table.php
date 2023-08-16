<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemarksToServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->text('remarks')->nullable();
            $table->text('notes')->nullable();
            $table->bigInteger('payment_mode_id')->unsigned()->nullable();
            $table->foreign('payment_mode_id')->references('id')->on('payment_mode')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('remarks');
            $table->dropColumn('notes');
            $table->dropColumn('payment_mode_id');
        });
    }
}
