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
        Schema::connection("mysql2")->create('newsystem_item_locations_live', function (Blueprint $table) {
            $table->id();
            $table->string("itemSKU")->nullable();
            $table->integer("item")->nullable();
            $table->string("configuration")->nullable();
            $table->string("size")->nullable();
            $table->string("colour")->nullable();
            $table->string("warehouse")->nullable();
            $table->string("pickingLocation")->nullable();
            $table->string("issueLocation")->nullable();
            $table->string("receiptLocation")->nullable();
            $table->dateTime("modifiedInventDim")->nullable();
            $table->dateTime("ModifiedInventItem")->nullable();
            $table->string("ICSC")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsystem_item_locations_live');
    }
};
