<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crypto_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol');
            $table->string('status')->default('active');
            $table->decimal('latest_quote', 18, 8)->nullable();
            $table->decimal('percent_change_1hr', 18, 8)->nullable();
            $table->decimal('percent_change_24hr', 18, 8)->nullable();
            $table->datetime('last_updated')->nullable();
            $table->json('price_graph_data_points')->nullable();
            $table->text('about')->nullable();
            $table->string('logo')->nullable();
            $table->string('term')->nullable();
            $table->string('market_cap')->nullable();
            $table->string('total_supply')->nullable();
            $table->string('volume')->nullable();
            $table->string('circulating_supply', 255)->nullable();
            $table->decimal('price', 20, 4)->nullable();
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
        Schema::dropIfExists('crypto_assets');
    }
};
