<?php

use App\Models\GiftcardCategory;
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
        Schema::create('giftcards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(GiftcardCategory::class)
                ->references('id')
                ->on('giftcard_categories')
                ->onDelete('cascade');
            $table->string('name');
            $table->string('image')->nullable();
            $table->integer('wait_time')->default(0);
            $table->bigInteger('minimum_amount')->default(100);
            $table->bigInteger('maximum_amount')->default(100);
            $table->enum('currency', ['NGN', 'USD', 'GBP', 'EUR'])->default('USD');
            $table->boolean('high_rate')->default(false);
            $table->boolean('active')->default(false);
            $table->string('terms')->nullable();
            $table->bigInteger('rate')->default(0);
            $table->integer('sort_order');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('giftcards');
    }
};
