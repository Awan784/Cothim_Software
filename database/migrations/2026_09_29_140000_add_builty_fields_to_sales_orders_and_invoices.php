<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('builty_postal', 100)->nullable()->after('mode');
            $table->decimal('builty_exp', 14, 2)->nullable()->after('builty_postal');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('builty_postal', 100)->nullable()->after('mode');
            $table->decimal('builty_exp', 14, 2)->nullable()->after('builty_postal');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['builty_postal', 'builty_exp']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['builty_postal', 'builty_exp']);
        });
    }
};
