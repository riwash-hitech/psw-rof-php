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
        Schema::connection("mysql2")->create('newsystem_item_by_locations', function (Blueprint $table) {
            $table->id();
            $table->string("ICSC")->nullable();
            $table->string("Item")->nullable();
            $table->string("Configuration")->nullable();
            $table->string("Colour")->nullable();
            $table->string("Size")->nullable();
            $table->string("Warehouse")->nullable();
            $table->string("PhysicalInventory")->nullable();
            $table->string("PhysicalReserved")->nullable();
            $table->string("AvailablePhysical")->nullable();
            $table->string("OrderedInTotal")->nullable();
            $table->string("OnOrder")->nullable();
            $table->string("ModifiedDateTime")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsystem_item_by_locations');
    }
};
