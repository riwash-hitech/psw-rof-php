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
        Schema::connection("mysql2")->create('newsystem_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string("ACCOUNTNUM")->nullable();
            $table->string("Name")->nullable();
            $table->bigInteger("RECID")->nullable();
            $table->dateTime("ModifiedDateTime")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsystem_suppliers');
    }
};
