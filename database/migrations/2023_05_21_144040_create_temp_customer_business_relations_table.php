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
        Schema::connection("mysql2")->create('temp_customer_business_relations', function (Blueprint $table) {
            $table->id();
            $table->string("PSW_SMMCUSTACCOUNT")->nullable();
            $table->string("NAME")->nullable();
            $table->string("ADDRESS")->nullable();
            $table->string("STREET")->nullable();
            $table->string("CITY")->nullable();
            $table->string("ZIPCODE")->nullable();
            $table->string("STATE")->nullable();
            $table->string("PHONE")->nullable();
            $table->string("EMAIL")->nullable();
            $table->string("CUSTGROUP")->nullable();
            $table->string("ERPLY_FLAG")->nullable();
            $table->string("SAB_RBOSTOREPRIMARY")->nullable();
            $table->string("SAB_RBOSTORESECONDARY")->nullable();
            $table->string("STATUS")->nullable();
            $table->string("CREDITMAX")->nullable();
            $table->string("MANDATORYCREDITLIMIT")->nullable();
            $table->dateTime("BusRelLastModified")->nullable();
            $table->dateTime("ERPLYFLAGModified")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_customer_business_relations');
    }
};
