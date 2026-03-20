<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_company_customer', function (Blueprint $table) {
            $table->id();
            $table->string('sap_code', 200)->unique();
            $table->string('customer_code', 200)->unique();
            $table->string('business_name', 50)->nullable();
            $table->enum('customer_type', ['0', '1', '2']);
            $table->string('owner_name', 200);
            $table->string('owner_no', 200);
            $table->enum('is_whatsapp', ['0','1'])->default('1');
            $table->string('whatsapp_no', 200)->nullable();
            $table->string('email', 200)->nullable();
            $table->string('language', 20);
            $table->string('contact_no2', 20)->nullable();
            $table->enum('buyerType', ['0','1'])->default('0');
            $table->string('road_street', 255)->nullable();
            $table->string('town', 255)->nullable();
            $table->string('landmark', 255)->nullable();
            $table->string('district', 255)->nullable();
            $table->decimal('balance', 18, 2)->default(0.00);
            $table->enum('payment_type', ['0','1','2','3'])->default('0');
            $table->string('bank_name', 255);
            $table->string('bank_account_number', 255);
            $table->string('creditday', 255);
            $table->string('tin_no', 255)->unique();
            $table->string('accuracy', 50)->nullable();
            $table->double('creditlimit', 18, 2)->default(0.00);
            $table->string('guarantee_name', 500);
            $table->decimal('guarantee_amount', 20, 2);
            $table->date('guarantee_from');
            $table->date('guarantee_to');
            $table->decimal('totalcreditlimit', 20, 2);
            $table->date('credit_limit_validity')->nullable();
            $table->unsignedBigInteger('region_id');
            $table->unsignedBigInteger('area_id');
            $table->string('vat_no', 30);
            $table->string('longitude', 255)->nullable();
            $table->string('latitude', 255)->nullable();
            $table->integer('threshold_radius');
            $table->unsignedBigInteger('dchannel_id');
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_user');
            $table->unsignedBigInteger('updated_user');
            $table->timestamps();

            // Foreign keys (if you have these tables)
            $table->foreign('region_id')->references('id')->on('tbl_region');
            $table->foreign('area_id')->references('id')->on('tbl_areas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_company_customer');
    }
};
