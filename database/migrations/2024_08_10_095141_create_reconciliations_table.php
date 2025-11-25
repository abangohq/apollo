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
        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id('id');
            $table->string('reference');
            $table->foreignId('user_id');
            $table->foreignId('staff_id')->nullable();
            $table->foreignId('origin_tranx_id')->nullable();
            $table->string('entry')->comment('debit, credit');
            $table->decimal('amount');
            $table->string('reason');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliations');
    }
};
