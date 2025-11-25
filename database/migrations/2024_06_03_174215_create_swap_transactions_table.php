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
        Schema::create('swap_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('swap_tranx_id')->index();
            $table->enum('swap_type', ['float', 'fixed'])->index();
            $table->string('status')->index();
            $table->string('currency_from');
            $table->string('currency_to');
            $table->string('payin_address');
            $table->string('payout_address');
            $table->boolean('is_app_address')->default(false);
            $table->decimal('amount_expected_from', 16, 8);
            $table->decimal('amount_expected_to', 16, 8);
            $table->dateTime('pay_till')->nullable()->index();
            $table->string('network_fee');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swap_transactions');
    }
};
