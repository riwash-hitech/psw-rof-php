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
        Schema::create('newsystem_inventory_registration_lines', function (Blueprint $table) {
            $table->id();
            $table->integer("warehouse")->nullable();
            $table->bigInteger("productID")->nullable();
            $table->string("price")->nullable();
            $table->integer("amount")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsystem_inventory_registration_lines');
    }
};
