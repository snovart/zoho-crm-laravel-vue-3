<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            // Add manager_id if missing
            if (!Schema::hasColumn('deals', 'manager_id')) {
                $table->unsignedBigInteger('manager_id')->nullable()->after('customer_id');
                $table->foreign('manager_id')
                    ->references('id')->on('managers')
                    ->nullOnDelete();
            }

            // Add source if missing
            if (!Schema::hasColumn('deals', 'source')) {
                $table->string('source')->after('customer_id');
            }

            // Composite index for filtering/ordering by source + manager
            // (Use explicit name so we can drop it reliably in down())
            if (Schema::hasColumn('deals', 'source') && Schema::hasColumn('deals', 'manager_id')) {
                $table->index(['source', 'manager_id'], 'deals_source_manager_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            // 1) Drop composite index first (if it exists)
            // We use a try/catch because Laravel has no portable "hasIndex" check.
            try {
                $table->dropIndex('deals_source_manager_id_index'); // matches the name used in up()
            } catch (\Throwable $e) {
                // Index might not exist or has a different name — ignore safely
            }

            // 2) Drop FK and column manager_id (if they exist)
            if (Schema::hasColumn('deals', 'manager_id')) {
                // Drop FK first to allow dropping the column
                try {
                    $table->dropForeign(['manager_id']);
                } catch (\Throwable $e) {
                    // FK might already be gone — ignore safely
                }

                $table->dropColumn('manager_id');
            }

            // 3) Drop source column ONLY if you created it in this migration.
            // If `source` existed before, remove this block to avoid deleting a legacy column.
            if (Schema::hasColumn('deals', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};
