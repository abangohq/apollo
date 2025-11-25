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
        Schema::create('crypto_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('rate_range');
            $table->decimal('rate', 10, 2);
            $table->decimal('range_start')->comment("The start of the USD range for this conversion range.");
            $table->decimal('range_end')->comment("The end of the USD range for this conversion range.");
            $table->boolean("is_published")->default(true)->comment("If it can be seen by users on mobile");
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
        Schema::dropIfExists('crypto_rates');
    }
};
