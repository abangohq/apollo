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
        Schema::create('crypto_wallet_address_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('crypto_wallet_id')->constrained('crypto_wallets')->cascadeOnDelete();
            $table->string('old_address');
            $table->string('new_address');
            $table->string('chain');
            $table->string('reason')->nullable(); // e.g., 'migration', 'security_update', 'user_request'
            $table->json('metadata')->nullable(); // Additional context like migration job name, admin user, etc.
            $table->timestamp('changed_at');
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['crypto_wallet_id', 'changed_at']);
            $table->index('old_address');
            $table->index('new_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_wallet_address_history');
    }
};
