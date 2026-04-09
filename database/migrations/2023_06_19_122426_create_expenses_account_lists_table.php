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
        Schema::connection("mysql2")->create('expenses_account_lists', function (Blueprint $table) {
            $table->id();
            $table->string("name")->nullable();
            $table->integer("accountType")->nullable();
            $table->integer("ledgerAccount")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses_account_lists');
    }
};
