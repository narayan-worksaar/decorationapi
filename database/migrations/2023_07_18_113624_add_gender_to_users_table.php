<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGenderToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('gender_id')->unsigned()->nullable();
            $table->foreign('gender_id')->references('id')->on('gender')->onUpdate('cascade');
            $table->string('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('landmark')->nullable();
            $table->bigInteger('city')->nullable();
            $table->bigInteger('state')->nullable();
            $table->string('pin_code')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gender_id');
            $table->dropColumn('date_of_birth');
            $table->dropColumn('address');
            $table->dropColumn('landmark');
            $table->dropColumn('city');
            $table->dropColumn('state');
            $table->dropColumn('pin_code');
        });
    }
}
