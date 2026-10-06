<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->string('print_note', 100)->nullable()->after('description');
        });

        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->string('print_note', 100)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->dropColumn('print_note');
        });

        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->dropColumn('print_note');
        });
    }
};
