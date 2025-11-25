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
        Schema::create('bybit_trades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('symbol');
            $table->string('side');
            $table->string('order_type');
            $table->string('quantity');
            $table->string('price')->nullable();
            $table->jsonb('response');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bybit_trades');
    }
};
