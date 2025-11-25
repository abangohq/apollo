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
        Schema::create('crypto_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('reference');
            $table->string('crypto');
            $table->decimal('crypto_amount', 18, 8);
            $table->unsignedDecimal('conversion_rate', 18, 2);
            $table->unsignedDecimal('usd_value', 18, 2);
            $table->decimal('payout_amount', 18);
            $table->string('payout_currency');
            $table->unsignedInteger('confirmations')->nullable();
            $table->string('status');
            $table->string('transaction_hash')->nullable();
            $table->string('transaction_link')->nullable();
            $table->string('address');
            $table->string('platform')->nullable();
            $table->unique(['transaction_hash', 'user_id'], 'crypto_tranx_hash_user_id');
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
        Schema::dropIfExists('crypto_transactions');
    }
};
