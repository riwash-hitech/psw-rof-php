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
        Schema::connection("mysql2")->create('live_sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string("SALESID");
      $table->string("OPENLINE");
      $table->string("erplysku1");
      $table->string("ITEMID");
      $table->string("CONFIGID");
      $table->string("INVENTCOLORID");
      $table->string("INVENTSIZEID");
      $table->string("SALESSTATUS");
      $table->string("CUSTACCOUNT");
      $table->string("SchoolAccount");
      $table->string("SALESPOOLID");
      $table->string("DeliveryMode");
      $table->string("DELIVERYNAME");
      $table->string("DELIVERYADDRESS");
      $table->string("DELIVERYSTREET");
      $table->string("DELIVERYCITY");
      $table->string("DELIVERYZIPCODE");
      $table->string("DELIVERYSTATE");
      $table->string("EMAIL");
      $table->string("Phone");
      $table->string("INVENTLOCATIONID");
      $table->string("WMSLOCATIONID");
      $table->string("SALESQTY");
      $table->string("REMAINSALESPHYSICAL");
      $table->string("SALESLINERECID");
      $table->dateTime("ModifiedDateTime");
      $table->dateTime("MODIFIEDDATETIME_SALESTABLE");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_sales_orders');
    }
};
