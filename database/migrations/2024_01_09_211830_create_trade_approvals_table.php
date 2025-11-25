<?php

use App\Models\Trade;
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
        Schema::create('trade_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(User::class, 'assigned_to')
                ->references('id')
                ->on('users');
            $table->foreignIdFor(Trade::class)
                ->references('id')
                ->on('trades');
            $table->enum('status', ['pending', 'rejected', 'approved'])->default('pending');
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_approvals');
    }
};
