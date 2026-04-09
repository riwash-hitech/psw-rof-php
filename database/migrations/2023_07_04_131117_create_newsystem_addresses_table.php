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
        Schema::create('newsystem_addresses', function (Blueprint $table) {
            $table->id();
            $table->string("clientCode")->nullable();
            $table->bigInteger("addressID")->nullable();
            $table->bigInteger("ownerID")->nullable();
            $table->integer("typeID")->nullable();
            $table->string("typeName")->nullable();
            $table->boolean("typeActivelyUsed")->nullable();
            $table->string("street")->nullable();
            $table->string("address2")->nullable();
            $table->string("city")->nullable();
            $table->string("postalCode")->nullable();
            $table->string("state")->nullable();
            $table->string("country")->nullable();
            $table->dateTime("added")->nullable();
            $table->dateTime("lastModified")->nullable();
            $table->string("lastModifierUsername")->nullable();
            $table->integer("lastModifierEmployeeID")->nullable();
            $table->text("attributes")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsystem_addresses');
    }
};
