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
         Schema::create('promotiongroups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('osa_code', 100)->unique();
            $table->string('name', 50);
            $table->smallInteger('item');
            $table->enum('status', ['0', '1'])->default('1');
            $table->smallInteger('created_user')->nullable();
            $table->smallInteger('updated_user')->nullable();
            $table->smallInteger('deleted_user')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
         }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotiongroups');
    }
};
