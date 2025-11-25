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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('phone')->unique()->nullable();
            $table->string('email')->unique();
            $table->string('passcode')->nullable();
            $table->date('dob')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('referral_code')->nullable();
            $table->string('device_token')->nullable();
            $table->string('device_type')->nullable();
            $table->string('status')->default('active');
            $table->string('face_id', 255)->nullable();
            $table->string('touch_id', 255)->nullable();
            $table->boolean('has_biometric')->default(false);
            $table->integer('tier_id')->default(1);
            $table->integer('credits')->nullable();
            $table->string('avatar')->nullable();
            $table->rememberToken();
            $table->string('pin')->nullable();
            $table->string('deleted_at')->nullable();
            $table->timestamps();
            $table->string('heard_about_us')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->enum('user_type', ['user', 'staff', 'admin'])->default('user')->index();
            $table->integer('role')->nullable()->index();


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
