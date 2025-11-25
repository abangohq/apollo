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
        Schema::table('crypto_rates', function (Blueprint $table) {
            $table->decimal('fee', 5, 2)->default(0)
                ->after('rate')
                ->comment('Percentage fee to apply for volatile coins (e.g., 12.00 for 12%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crypto_rates', function (Blueprint $table) {
            $table->dropColumn('fee');
        });
    }
};