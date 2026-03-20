<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_vehicle', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_code', 100)->unique();
            $table->string('number_plat', 255);
            $table->string('vehicle_chesis_no', 255)->unique();
            $table->text('description')->nullable();
            $table->string('capacity', 255);
            $table->enum('vehicle_type', ['0', '1', '2', '3'])->default('0'); // 0=Tuktuk,1=Truck,2=Tricycle,3=Van
            $table->enum('owner_type', ['0', '1'])->default('0'); // 0=Hariss,1=Warehouse
            $table->unsignedBigInteger('warehouse_id');
            $table->date('valid_from');
            $table->date('valid_to');
            $table->string('opening_odometer', 255);
            $table->tinyInteger('status')->default(1); // 1=Active,0=Inactive
            $table->unsignedBigInteger('created_user');
            $table->unsignedBigInteger('updated_user');
            $table->timestamp('created_date')->useCurrent();

            // Foreign key
            $table->foreign('warehouse_id')->references('id')->on('tbl_warehouse');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_vehicle');
    }
};
