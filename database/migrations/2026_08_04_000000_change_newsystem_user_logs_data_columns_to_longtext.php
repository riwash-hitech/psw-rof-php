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
        Schema::table('newsystem_user_logs', function (Blueprint $table) {
            $table->longText('previousData')->nullable()->change();
            $table->longText('newData')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsystem_user_logs', function (Blueprint $table) {
            $table->text('previousData')->nullable()->change();
            $table->text('newData')->nullable()->change();
        });
    }
};
