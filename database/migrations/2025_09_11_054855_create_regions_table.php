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
        Schema::create('tbl_region', function (Blueprint $table) {
            $table->id(); // id INT(11) primary key
            $table->string('region_code', 200);
            $table->string('region_name', 200);
            // foreign key to tbl_country.id
            $table->unsignedBigInteger('country_id');
            $table->foreign('country_id')
                  ->references('id')
                  ->on('tbl_country')
                  ->onDelete('cascade');
            $table->tinyInteger('status')->default(1); // active/inactive
            $table->integer('created_user')->nullable();
            $table->integer('updated_user')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('updated_date')->nullable()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_region', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
        });
        Schema::dropIfExists('tbl_region');
    }
};
