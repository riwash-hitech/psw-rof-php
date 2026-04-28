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
        Schema::create('erply_sync_dates', function (Blueprint $table) {
            $table->id();
            $table->dateTime('matrix_product_added')->nullable();
            $table->dateTime('variation_product_added')->nullable();
            $table->dateTime('matrix_product_last_modified')->nullable();
            $table->dateTime('variation_product_last_modified')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erply_sync_dates');
    }
};
