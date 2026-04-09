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
        Schema::connection("mysql2")->create('newsystem_delivery_modes_tables', function (Blueprint $table) {
            $table->id();
            $table->string("CODE")->nullable();
            $table->string("TXT")->nullable();
            $table->string("dmxCarrierName")->nullable();
            $table->dateTime("createdDateTime")->nullable();
            $table->dateTime("modifiedDateTime")->nullable();
            $table->string("dataAreaID")->nullable();
            $table->bigInteger("RECID")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_newsystem_delivery_modes_tables');
    }
};
