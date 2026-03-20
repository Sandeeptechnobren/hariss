<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tbl_warehouse', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code', 200);
            $table->string('warehouse_name', 200);
            $table->string('owner_name', 250)->nullable();
            $table->string('owner_number', 100)->nullable();
            $table->string('owner_email', 100)->nullable();
            $table->unsignedBigInteger('agent_id');
            $table->string('warehouse_manager', 50);
            $table->string('warehouse_manager_contact', 20);
            $table->string('tin_no', 30)->unique();
            $table->string('registation_no', 30)->unique();
            $table->enum('business_type', ['0', '1']);
            $table->enum('warehouse_type', ['0', '1', '2']);
            $table->string('city', 250);
            $table->string('location', 250);
            $table->string('address', 250);
            $table->string('stock_capital', 500)->nullable();
            $table->string('deposite_amount', 500)->nullable();
            $table->unsignedBigInteger('region_id');
            $table->unsignedBigInteger('area_id');
            $table->string('latitude', 250);
            $table->string('longitude', 250);
            $table->string('device_no', 500);
            $table->string('p12_file', 500);
            $table->string('password', 500);
            $table->string('branch_id', 500)->nullable();
            $table->enum('is_branch', ['0', '1'])->nullable();
            $table->enum('invoice_sync', ['0', '1'])->nullable();
            $table->integer('status');
            $table->enum('is_efris', ['0', '1'])->default('1');
            $table->unsignedBigInteger('created_user');
            $table->unsignedBigInteger('updated_user');
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('updated_date')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes('deleted_date'); 

            $table->foreign('region_id')->references('id')->on('tbl_region')->onDelete('set null');
            $table->foreign('area_id')->references('id')->on('tbl_areas')->onDelete('set null');
            $table->foreign('created_user')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_user')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['status']);
            $table->index(['agent_id']);
            $table->index(['region_id']);
            $table->index(['area_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_warehouse');
    }
};