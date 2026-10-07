<?php

use App\Models\StockItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $itemIds = DB::table('stock_item_lots')
            ->select('stock_item_id')
            ->groupBy('stock_item_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('stock_item_id');

        foreach ($itemIds as $itemId) {
            $item = StockItem::withoutGlobalScopes()->find($itemId);
            if ($item) {
                DB::transaction(fn () => $item->collapseToSingleLot());
            }
        }

        Schema::table('stock_item_lots', function (Blueprint $table) {
            $table->dropUnique(['stock_item_id', 'batch_no']);
            $table->unique('stock_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('stock_item_lots', function (Blueprint $table) {
            $table->dropUnique(['stock_item_id']);
            $table->unique(['stock_item_id', 'batch_no']);
        });
    }
};
