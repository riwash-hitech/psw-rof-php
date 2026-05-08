<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AddDatabaseIndexes extends Command
{
    protected $signature = 'db:add-indexes';
    protected $description = 'Add indexes to live product tables on multiple DB connections';

    public function handle(): int
    {
        $this->info("Starting index creation...");

        $connection = 'mysql2';

        $addIndexIfNotExists = function ($table, $indexName, $columns) use ($connection) {

            // Get existing indexes
            $existingIndexes = \DB::connection($connection)
                ->select("SHOW INDEX FROM {$table}");

            foreach ($existingIndexes as $index) {
                if ($index->Key_name === $indexName) {
                    $this->warn("Skipping existing index: {$indexName} on {$table}");
                    return;
                }
            }

            // Create index safely
            \Schema::connection($connection)->table($table, function ($blueprint) use ($indexName, $columns) {
                $blueprint->index($columns, $indexName);
            });

            $this->info("Created index: {$indexName} on {$table}");
        };

        // =========================
        // LIVE PRODUCT MATRIX
        // =========================
        $addIndexIfNotExists('newsystem_product_matrix_live', 'idx_erplyID_matrix', 'erplyID');
        $addIndexIfNotExists('newsystem_product_matrix_live', 'idx_erplyPending_matrix', 'erplyPending');
        $addIndexIfNotExists('newsystem_product_matrix_live', 'idx_school_matrix', 'SchoolID');
        $addIndexIfNotExists('newsystem_product_matrix_live', 'idx_template_matrix', 'SOFTemplate');
        $addIndexIfNotExists('newsystem_product_matrix_live', 'idx_flag_matrix', 'ERPLYFLAG');
        $addIndexIfNotExists('newsystem_product_matrix_live', 'idx_default_store_matrix', 'DefaultStore');
        $addIndexIfNotExists('newsystem_product_matrix_live', 'idx_secondary_store_matrix', 'SecondaryStore');
        $addIndexIfNotExists('newsystem_product_matrix_live', 'idx_modified_matrix', 'ItemLastModified');

        // =========================
        // LIVE PRODUCT VARIATION
        // =========================
        $addIndexIfNotExists('newsystem_product_variation_live', 'idx_erplyID_var', 'erplyID');
        $addIndexIfNotExists('newsystem_product_variation_live', 'idx_erplyPending_var', 'erplyPending');
        $addIndexIfNotExists('newsystem_product_variation_live', 'idx_school_var', 'SchoolID');
        $addIndexIfNotExists('newsystem_product_variation_live', 'idx_template_var', 'SOFTemplate');
        $addIndexIfNotExists('newsystem_product_variation_live', 'idx_flag_var', 'ERPLYFLAG');
        $addIndexIfNotExists('newsystem_product_variation_live', 'idx_default_store_var', 'DefaultStore');
        $addIndexIfNotExists('newsystem_product_variation_live', 'idx_secondary_store_var', 'SecondaryStore');
        $addIndexIfNotExists('newsystem_product_variation_live', 'idx_modified_var', 'ItemLastModified');
        $addIndexIfNotExists('newsystem_product_variation_live', 'idx_icsc_var', 'ICSC');
        $addIndexIfNotExists('newsystem_product_variation_live', 'idx_mrfCode_var', 'mfrCode');
        $addIndexIfNotExists('newsystem_product_variation_live', 'idx_eanBarcode_var', 'EANBarcode');

        // =========================
        // PRODUCT GROUP / LOCATION
        // =========================
        // $addIndexIfNotExists('newsystem_item_by_locations', 'idx_parentGroupID', 'parentGroupID');
        // $addIndexIfNotExists('newsystem_item_by_locations', 'idx_productGroupID', 'productGroupID');

        $this->info("All indexes processed successfully on mysql2.");

        return Command::SUCCESS;
    }
}
