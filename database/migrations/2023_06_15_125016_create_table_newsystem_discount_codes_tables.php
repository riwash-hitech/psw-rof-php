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
        Schema::connection("mysql2")->create('newsystem_discount_codes_tables', function (Blueprint $table) {
            $table->id();
            $table->string("INFOCODEID")->nullable();
            $table->string("SUBCODEID")->nullable();
            $table->string("DESCRIPTION")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_newsystem_discount_codes_tables');
    }
};
