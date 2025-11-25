<?php

use App\Models\Giftcard;
use App\Models\User;
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
        Schema::create('trades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Giftcard::class)
                ->references('id')
                ->on('giftcards');
            $table->foreignIdFor(User::class)
                ->references('id')
                ->on('users');
            $table->bigInteger('amount');
            $table->bigInteger('rate')->default(0);
            $table->enum('status', ['pending', 'rejected', 'approved'])->default('pending');
            $table->enum('currency', ['NGN', 'USD', 'GBP', 'EUR'])->default('USD');
            $table->string('e_code')->nullable();
            $table->enum('payout_method', ['NGN', 'USDT'])->default('NGN');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
