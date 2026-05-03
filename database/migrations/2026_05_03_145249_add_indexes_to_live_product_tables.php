<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // LiveProductMatrix table
        Schema::table('live_product_matrix', function (Blueprint $table) {
            $table->index('erplyID');
            $table->index('erplyPending');
            $table->index('SchoolID');
            $table->index('SOFTemplate');
            $table->index('ERPLYFLAG');
            $table->index('DefaultStore');
            $table->index('SecondaryStore');
            $table->index('ItemLastModified');
            $table->index('ICSC');
        });

        // LiveProductVariation table
        Schema::table('live_product_variation', function (Blueprint $table) {
            $table->index('erplyID');
            $table->index('erplyPending');
            $table->index('SchoolID');
            $table->index('SOFTemplate');
            $table->index('ERPLYFLAG');
            $table->index('DefaultStore');
            $table->index('SecondaryStore');
            $table->index('ItemLastModified');
            $table->index('ICSC');
        });

        // ProductGroup table
        Schema::table('product_group', function (Blueprint $table) {
            $table->index('clientCode');
            $table->index('parentGroupID');
            $table->index('productGroupID');
        });
    }

    public function down(): void
    {
        Schema::table('live_product_matrix', function (Blueprint $table) {
            $table->dropIndex(['erplyID']);
            $table->dropIndex(['erplyPending']);
            $table->dropIndex(['SchoolID']);
            $table->dropIndex(['SOFTemplate']);
            $table->dropIndex(['ERPLYFLAG']);
            $table->dropIndex(['DefaultStore']);
            $table->dropIndex(['SecondaryStore']);
            $table->dropIndex(['ItemLastModified']);
            $table->dropIndex(['ICSC']);
        });

        Schema::table('live_product_variation', function (Blueprint $table) {
            $table->dropIndex(['erplyID']);
            $table->dropIndex(['erplyPending']);
            $table->dropIndex(['SchoolID']);
            $table->dropIndex(['SOFTemplate']);
            $table->dropIndex(['ERPLYFLAG']);
            $table->dropIndex(['DefaultStore']);
            $table->dropIndex(['SecondaryStore']);
            $table->dropIndex(['ItemLastModified']);
            $table->dropIndex(['ICSC']);
        });

        Schema::table('product_group', function (Blueprint $table) {
            $table->dropIndex(['clientCode']);
            $table->dropIndex(['parentGroupID']);
            $table->dropIndex(['productGroupID']);
        });
    }
};
