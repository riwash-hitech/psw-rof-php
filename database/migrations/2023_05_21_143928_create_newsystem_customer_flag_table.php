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
        Schema::connection("mysql2")->create('newsystem_customer_flag', function (Blueprint $table) {
            $table->id();
            $table->string("ACCOUNTNUM");
            $table->string("NAME")->nullable();
            $table->string("CUSTGROUP")->nullable();
            $table->string("ACADEMYFLAG")->nullable();
            $table->string("PSWFLAG")->nullable();
            $table->string("ERPLYFLAG")->nullable();
            $table->string("ERPLYFLAGModified")->nullable();
            $table->string("ENTITY")->nullable();
            $table->dateTime("ModifiedDateTime")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsystem_customer_flag');
    }
};
