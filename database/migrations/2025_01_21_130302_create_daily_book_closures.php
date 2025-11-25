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
        Schema::create('daily_book_closures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->decimal('total_credits', 15, 2);
            $table->decimal('total_debits', 15, 2);
            $table->decimal('total_charges', 15, 2);
            $table->integer('total_transactions');
            $table->decimal('net_position', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_book_closures');
    }
};
