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
        Schema::connection("mysql2")->create('temp_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string("PURCHID")->nullable();
            $table->string("PURCHSTATUS")->nullable();
            $table->string("PSW_CROSSDOCKWAREHOUSE")->nullable();
            $table->string("PURCHNAME")->nullable();
            $table->string("ITEMID")->nullable();
            $table->string("CONFIGID")->nullable();
            $table->string("INVENTSIZEID")->nullable();
            $table->string("INVENTCOLORID")->nullable();
            $table->string("PURCHQTY")->nullable();
            $table->string("REMAINPURCHPHYSICAL")->nullable();
            $table->string("LastModifiedDateTime")->nullable();
            $table->string("PSW_REPORTWAREHOUSECAT")->nullable();
            $table->tinyInteger("pendingProcess")->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_purchase_orders');
    }
};
