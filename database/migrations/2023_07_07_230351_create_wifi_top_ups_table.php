<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wifi_top_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('product');
            $table->string('name');
            $table->string('code');
            $table->string('device_number');
            $table->decimal('amount_requested', 8, 2);
            $table->decimal('amount_paid', 10, 2)->nullable()->default(0);
            $table->decimal('discount_percentage', 10, 2)->nullable()->default(0);
            $table->decimal('discount_value', 10, 2)->nullable()->default(0);
            $table->string('reference');
            $table->string('status');
            $table->string('provider_status')->nullable();
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
        Schema::dropIfExists('wifi_top_ups');
    }
};
