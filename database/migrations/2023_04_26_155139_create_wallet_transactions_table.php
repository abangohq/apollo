<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Status;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('transaction_type');
            $table->foreignId('transaction_id')->nullable()->index();
            $table->enum('entry', ['credit', 'debit'])->nullable()->index();
            $table->char('status')->default('processing');
            $table->string('narration')->nullable()->index();
            $table->char('currency', 10)->default('NGN');
            $table->decimal('amount', 18, 2)->comment('transaction amount');
            $table->decimal('charge', 18, 4)->default(0);
            $table->decimal('total_amount', 18, 4);
            $table->decimal('wallet_balance', 12)->default(0)->index();
            $table->boolean('is_reversal')->default(false)->index();
            $table->string('mode')->nullable()->comment('mode of transaction');
            $table->index(['transaction_type', 'transaction_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
