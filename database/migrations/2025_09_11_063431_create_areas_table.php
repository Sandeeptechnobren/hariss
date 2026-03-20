<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tbl_areas', function (Blueprint $table) {
            $table->id();
            $table->string('area_code', 200)->nullable();
            $table->string('area_name', 200);
            $table->unsignedBigInteger('region_id');
            $table->tinyInteger('status')->nullable()->default(0);
            $table->unsignedBigInteger('created_user');
            $table->unsignedBigInteger('updated_user');
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('updated_date')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('region_id')->references('id')->on('tbl_region')->onDelete('cascade');
            $table->foreign('created_user')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_areas');
    }
};
