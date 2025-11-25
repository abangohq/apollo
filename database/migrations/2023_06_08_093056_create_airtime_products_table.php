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
        Schema::create('airtime_products', function (Blueprint $table) {
            $table->id();
            $table->string('product');
            $table->string('code');
            $table->string('logo');
            $table->string('status');
            $table->unsignedInteger('minimum_amount');
            $table->unsignedInteger('maximum_amount');
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
        Schema::dropIfExists('airtime_products');
    }
};
