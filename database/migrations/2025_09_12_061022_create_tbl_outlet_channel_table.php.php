<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_channel', function (Blueprint $table) {
            $table->id();
            $table->string('outlet_channel_code', 50)->unique();
            $table->string('outlet_channel', 255);
            $table->tinyInteger('status')->default(0)->comment('0=Active,1=Inactive');
            $table->unsignedBigInteger('created_user');
            $table->unsignedBigInteger('updated_user')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('updated_date')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_channel');
    }
};
