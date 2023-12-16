<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_group', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('dealer_id')->unsigned()->nullable();
            $table->foreign('dealer_id')->references('id')->on('users')->onUpdate('cascade');
            $table->string('group_id')->nullable();
            $table->string('chat_id')->nullable();
            $table->bigInteger('whatsapp_integration_id')->unsigned()->nullable();
            $table->foreign('whatsapp_integration_id')->references('id')->on('whatsapp_integration')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_group');
    }
};
