<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('reference');
            $table->decimal('amount', 18, 2);
            $table->string('bank_code')->nullable()->index();
            $table->foreignId('bank_id')->nullable();
            $table->string('bank_name')->nullable()->index();
            $table->string('account_name')->nullable()->index();
            $table->string('account_number')->nullable()->index();
            $table->text('bank_logo')->nullable();
            $table->char('status')->index();
            $table->unsignedBigInteger('rejection_id')->nullable();
            $table->foreign('rejection_id')->references('id')->on('rejection_reasons');
            $table->integer('settled_by')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('provider_status')->nullable();
            $table->string('platform')->nullable();
            $table->enum('channel', ['automated', 'manual'])->default('automated')->index();
            $table->timestamps();
            $table->index([DB::raw('created_at DESC')], 'withdrawals_created_at_desc_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('withdrawals');
    }
};
