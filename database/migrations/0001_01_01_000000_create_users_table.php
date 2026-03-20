<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
           $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('username')->nullable();
            $table->string('profile')->nullable();
            $table->integer('role')->nullable()->comment('1=admin,0=user');
            $table->integer('status');
            $table->string('region_id')->nullable();
            $table->string('subregion_id')->nullable();
            $table->integer('salesman_id');
            $table->string('subdepot_id', 200);
            $table->integer('Modifier_Id')->nullable();
            $table->string('Modifier_Name')->nullable();
            $table->dateTime('Modifier_Date')->nullable();
            $table->dateTime('Login_Date');
            $table->integer('is_list')->comment('1=yes,0=no');
            $table->integer('created_user');
            $table->integer('updated_user');
            $table->dateTime('Created_Date');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
