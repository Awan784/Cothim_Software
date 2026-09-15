<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->decimal('discount_amount', 14, 2)->default(0)->after('subtotal');
        });

        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->decimal('discount_rate', 5, 2)->default(0)->after('unit_price');
            $table->decimal('discount_amount', 14, 2)->default(0)->after('discount_rate');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });

        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['discount_rate', 'discount_amount']);
        });
    }
};
