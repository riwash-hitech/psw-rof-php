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
        Schema::connection("mysql2")->create('temp_on_hand_inventory', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("Item")->nullable();
            $table->string("Configuration")->nullable();
            $table->string("Colour")->nullable();
            $table->string("Size")->nullable();
            $table->string("Warehouse")->nullable();
            $table->string("Location")->nullable();
            $table->integer("PhysicalInventory")->nullable();
            $table->integer("PhysicalReserved")->nullable();
            $table->integer("AvailablePhysical")->nullable();
            $table->integer("OrderInTotal")->nullable();
            $table->integer("OnOrder")->nullable();
            $table->dateTime("ModifiedDateTime")->nullable();
            $table->string("ERPLYSKU")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_on_hand_inventory');
    }
};
