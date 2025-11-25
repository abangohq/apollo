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
        Schema::create('meter_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();

            $table->timestamps();
        });

        DB::table('meter_products')->insert([
            ['name' => 'Abuja'],
            ['name' => 'Eko'],
            ['name' => 'Enugu'],
            ['name' => 'Jos'],
            ['name' => 'Ibadan'],
            ['name' => 'Ikeja'],
            ['name' => 'Kaduna'],
            ['name' => 'Kano'],
            ['name' => 'Porthacourt'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meter_products');
    }
};
