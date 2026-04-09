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
        Schema::connection("mysql2")->create('temp_item_by_icsc', function (Blueprint $table) {
            $table->id();
            $table->string("ICSC")->nullable();
            $table->string("Item")->nullable();
            $table->string("Configuration")->nullable();
            $table->string("Colour")->nullable();
            $table->string("Size")->nullable();
            $table->string("PhysicalInventory")->nullable();
            $table->string("PhysicalReserved")->nullable();
            $table->string("Available Physical")->nullable();
            $table->string("OrderedInTotal")->nullable();
            $table->string("OnOrder")->nullable();
            $table->dateTime("ModifiedDateTime")->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_item_by_icsc');
    }
};
