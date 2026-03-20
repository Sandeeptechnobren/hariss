<?php

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
        Schema::create('tbl_country', function (Blueprint $table) {
            $table->id(); // Primary Key (id INT AUTO_INCREMENT / SERIAL)
            $table->string('country_code', 10)->unique();
            $table->string('country_name', 150);
            $table->string('currency', 50)->nullable();
            $table->integer('status')->default(1); // 1 = active, 0 = inactive

            $table->integer('created_user')->nullable();
            $table->integer('updated_user')->nullable();

            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('updated_date')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_country');
    }
};
