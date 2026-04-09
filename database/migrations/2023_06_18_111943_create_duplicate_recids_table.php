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
        Schema::create('duplicate_recids', function (Blueprint $table) {
            $table->id();
            $table->integer("tableID")->nullable();
            $table->bigInteger("RECID")->nullable();
            $table->string("tableName")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duplicate_recids');
    }
};
