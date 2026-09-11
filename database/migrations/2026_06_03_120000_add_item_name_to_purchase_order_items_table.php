<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['stock_item_id']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('stock_item_id')->nullable()->change();
            $table->string('item_name')->nullable()->after('stock_item_id');
            $table->foreign('stock_item_id')->references('id')->on('stock_items')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['stock_item_id']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('item_name');
            $table->foreignId('stock_item_id')->nullable(false)->change();
            $table->foreign('stock_item_id')->references('id')->on('stock_items')->cascadeOnUpdate()->restrictOnDelete();
        });
    }
};
