<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropUniqueIfPresent('stock_item_lots', ['stock_item_id']);
        $this->addUniqueIfMissing('stock_item_lots', ['stock_item_id', 'batch_no']);

        Schema::table('stock_item_lots', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_item_lots', 'manufactured_at')) {
                $table->date('manufactured_at')->nullable()->after('batch_no');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_order_items', 'manufactured_at')) {
                $table->date('manufactured_at')->nullable()->after('batch_no');
            }
            if (! Schema::hasColumn('purchase_order_items', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('manufactured_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'expiry_date')) {
                $table->dropColumn('expiry_date');
            }
            if (Schema::hasColumn('purchase_order_items', 'manufactured_at')) {
                $table->dropColumn('manufactured_at');
            }
        });

        Schema::table('stock_item_lots', function (Blueprint $table) {
            if (Schema::hasColumn('stock_item_lots', 'manufactured_at')) {
                $table->dropColumn('manufactured_at');
            }
        });

        $this->dropUniqueIfPresent('stock_item_lots', ['stock_item_id', 'batch_no']);
        $this->addUniqueIfMissing('stock_item_lots', ['stock_item_id']);
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropUniqueIfPresent(string $tableName, array $columns): void
    {
        $name = $this->uniqueName($tableName, $columns);
        if ($name === null) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($name) {
            $table->dropUnique($name);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function addUniqueIfMissing(string $tableName, array $columns): void
    {
        if ($this->uniqueName($tableName, $columns) !== null) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns) {
            $table->unique($columns);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function uniqueName(string $tableName, array $columns): ?string
    {
        foreach (Schema::getIndexes($tableName) as $index) {
            if (empty($index['unique']) || ! empty($index['primary'])) {
                continue;
            }
            if (($index['columns'] ?? []) === $columns) {
                return $index['name'] ?? null;
            }
        }

        return null;
    }
};
