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
        Schema::create('cable_top_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('product');
            $table->string('phone_no')->nullable();
            $table->string('name');
            $table->string('code');
            $table->string('smart_card_no');
            $table->string('customer_name')->nullable();
            $table->string('amount_requested');
            $table->decimal('amount_paid', 10)->nullable();
            $table->integer('discount_percentage')->nullable();
            $table->decimal('discount_value')->nullable();
            $table->string('reference');
            $table->string('status');
            $table->string('provider_status')->nullable()->index();
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
        Schema::dropIfExists('cable_top_ups');
    }
};
