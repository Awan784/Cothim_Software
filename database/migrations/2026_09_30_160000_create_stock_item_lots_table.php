<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_item_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('stock_item_id')->constrained('stock_items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('batch_no', 100)->default('');
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 14, 2)->default(0);
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->unique(['stock_item_id', 'batch_no']);
            $table->index(['stock_item_id', 'received_at']);
        });

        $this->addLotColumn('purchase_order_items');
        $this->addLotColumn('sales_invoice_lines');
        $this->addLotColumn('sales_order_lines');
        $this->addLotColumn('sales_return_items');

        if (Schema::hasTable('stock_movements') && ! Schema::hasColumn('stock_movements', 'stock_item_lot_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->foreignId('stock_item_lot_id')
                    ->nullable()
                    ->after('stock_item_id')
                    ->constrained('stock_item_lots')
                    ->nullOnDelete();
            });
        }

        $now = now();
        $items = DB::table('stock_items')->select('id', 'organization_id', 'batch_no', 'quantity', 'expiry_date', 'created_at')->get();

        foreach ($items as $item) {
            $exists = DB::table('stock_item_lots')->where('stock_item_id', $item->id)->exists();
            if ($exists) {
                continue;
            }

            $qty = round((float) $item->quantity, 2);
            $batch = trim((string) ($item->batch_no ?? ''));
            if ($qty == 0.0 && $batch === '') {
                continue;
            }

            DB::table('stock_item_lots')->insert([
                'organization_id' => $item->organization_id,
                'stock_item_id' => $item->id,
                'batch_no' => $batch,
                'expiry_date' => $item->expiry_date,
                'quantity' => $qty,
                'received_at' => $item->created_at ?: $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_movements', 'stock_item_lot_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropConstrainedForeignId('stock_item_lot_id');
            });
        }

        foreach (['purchase_order_items', 'sales_invoice_lines', 'sales_order_lines', 'sales_return_items'] as $tableName) {
            $this->dropLotColumn($tableName);
        }

        Schema::dropIfExists('stock_item_lots');
    }

    private function addLotColumn(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (! Schema::hasColumn($tableName, 'stock_item_lot_id')) {
                $table->foreignId('stock_item_lot_id')
                    ->nullable()
                    ->after('stock_item_id')
                    ->constrained('stock_item_lots')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn($tableName, 'batch_no')) {
                $table->string('batch_no', 100)->nullable()->after('stock_item_lot_id');
            }
        });
    }

    private function dropLotColumn(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasColumn($tableName, 'stock_item_lot_id')) {
                $table->dropConstrainedForeignId('stock_item_lot_id');
            }
            if (Schema::hasColumn($tableName, 'batch_no')) {
                $table->dropColumn('batch_no');
            }
        });
    }
};
