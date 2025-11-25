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
        Schema::table('crypto_transactions', function (Blueprint $table) {
            $table->unsignedDecimal('fee', 18, 2)
                ->default(0)
                ->after('usd_value')
                ->comment('Calculated fee amount in USD for this transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crypto_transactions', function (Blueprint $table) {
            $table->dropColumn('fee');
        });
    }
};