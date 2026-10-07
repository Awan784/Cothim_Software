<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(0)->after('discount_amount');
        });

        foreach (DB::table('sales_invoice_lines')->select('id', 'line_net', 'commission_amount')->cursor() as $line) {
            $net = (float) $line->line_net;
            $amount = (float) $line->commission_amount;
            $rate = ($amount > 0 && $net > 0)
                ? min(100, round($amount / $net * 100, 2))
                : 0;

            DB::table('sales_invoice_lines')->where('id', $line->id)->update([
                'commission_rate' => $rate,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }
};
