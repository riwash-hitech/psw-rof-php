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
        Schema::create('newsystem_product_stocks', function (Blueprint $table) {
            $table->id();
            $table->string("clientCode")->nullable();
            $table->bigInteger("warehouseID")->nullable();
            $table->bigInteger("productID")->nullable();
            $table->integer("amountInStock")->nullable();
            $table->date("lastSoldDate")->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsystem_product_stocks');
    }
};
