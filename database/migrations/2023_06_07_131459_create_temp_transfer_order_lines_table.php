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
        Schema::connection("mysql2")->create('temp_transfer_order_lines', function (Blueprint $table) {
            $table->id();
            $table->string("TransferNumber")->nullable();
            $table->integer("TransferStatus")->nullable();
            $table->string("ItemNumber")->nullable();
            $table->string("Configuration")->nullable();
            $table->string("Colour")->nullable();
            $table->string("Size")->nullable();
            $table->string("FromWarehouse")->nullable();
            $table->string("FromLocation")->nullable();
            $table->double("Quantity")->nullable();
            $table->double("ShippedQty")->nullable();
            $table->double("ShipRemaining")->nullable();
            $table->string("ToWarehouse")->nullable();
            $table->double("ReceivedQty")->nullable();
            $table->double("ReceivedRemain")->nullable();
            $table->dateTime("HeaderModifiedDateTime")->nullable();
            $table->dateTime("LineModifiedDateTime")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_transfer_order_lines');
    }
};
