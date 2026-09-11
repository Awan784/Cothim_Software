<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_categories', function (Blueprint $table) {
            $table->string('kind', 50)->nullable()->after('name');
        });

        Schema::table('stock_items', function (Blueprint $table) {
            $table->string('batch_no', 100)->nullable()->after('sku');
            $table->boolean('has_variants')->default(false)->after('sale_price');
            $table->text('description')->nullable()->after('has_variants');
            $table->string('potency', 50)->nullable()->after('description');
            $table->string('pack_size', 100)->nullable()->after('potency');
            $table->string('manufacturer')->nullable()->after('pack_size');
            $table->date('expiry_date')->nullable()->after('manufacturer');
            $table->text('composition')->nullable()->after('expiry_date');
            $table->string('barcode', 100)->nullable()->after('composition');
            $table->unsignedInteger('pcs_per_box')->nullable()->after('barcode');
            $table->unsignedInteger('boxes_per_carton')->nullable()->after('pcs_per_box');
            $table->string('storage_note')->nullable()->after('boxes_per_carton');
            $table->string('hs_code', 50)->nullable()->after('storage_note');
        });

        Schema::create('stock_item_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('stock_item_id')->constrained('stock_items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('size', 100)->nullable();
            $table->decimal('price', 14, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_item_variants');

        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropColumn([
                'batch_no',
                'has_variants',
                'description',
                'potency',
                'pack_size',
                'manufacturer',
                'expiry_date',
                'composition',
                'barcode',
                'pcs_per_box',
                'boxes_per_carton',
                'storage_note',
                'hs_code',
            ]);
        });

        Schema::table('stock_categories', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
