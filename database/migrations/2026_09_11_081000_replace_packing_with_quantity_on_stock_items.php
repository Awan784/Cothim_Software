<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->decimal('quantity', 14, 2)->default(0)->after('unit');

            if (Schema::hasColumn('stock_items', 'pcs_per_box')) {
                $table->dropColumn('pcs_per_box');
            }
            if (Schema::hasColumn('stock_items', 'boxes_per_carton')) {
                $table->dropColumn('boxes_per_carton');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropColumn('quantity');
            $table->unsignedInteger('pcs_per_box')->nullable();
            $table->unsignedInteger('boxes_per_carton')->nullable();
        });
    }
};
