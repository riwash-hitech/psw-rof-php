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
        Schema::table('erply_sync_datetimes', function (Blueprint $table) {
            $table->dateTime('psw_customer_modified')->nullable()->after('psw_customer_added');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erply_sync_datetimes', function (Blueprint $table) {
            $table->dropColumn('psw_customer_modified');
        });
    }
};
