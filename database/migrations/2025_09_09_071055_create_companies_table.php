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
        Schema::create('tbl_company', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('company_code')->unique();
            $table->string('company_name');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('vat')->nullable();
            
            $table->unsignedBigInteger('country_id');
            $table->string('selling_currency')->nullable();
            $table->string('purchase_currency')->nullable();
            $table->string('toll_free_no')->nullable();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();

            // ENUM fields
            $table->enum('service_type', ['branch', 'warehouse'])->default('branch');
            $table->enum('company_type', ['trading', 'manufacturing'])->default('trading');
            $table->enum('status', ['active', 'inactive'])->default('active');

            // JSON for module access
            $table->json('module_access')->nullable();

            // Relationships
            // $table->foreign('country_id')->references('id')->on('tbl_country')->onDelete('cascade');

            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_company');
    }
};
