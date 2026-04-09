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
        Schema::create('newsystem_priority_groups', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("priorityGroupID")->nullable();
            $table->string("priorityGroupName")->nullable();
            $table->dateTime("added")->nullable();
            $table->dateTime("lastModified")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsystem_priority_groups');
    }
};
