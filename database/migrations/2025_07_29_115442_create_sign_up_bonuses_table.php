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
        Schema::create('sign_up_bonuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('bonus_amount', 10, 2)->default(500.00);
            $table->decimal('required_trade_volume', 10, 2)->default(200.00);
            $table->decimal('current_trade_volume', 10, 2)->default(0.00);
            $table->enum('status', ['pending', 'unlocked', 'claimed', 'expired'])->default('pending');
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sign_up_bonuses');
    }
};
