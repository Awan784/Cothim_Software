<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salesman_settlements', function (Blueprint $table) {
            $table->decimal('opening_receivable', 14, 2)->default(0)->after('closing_advance');
            $table->decimal('applied_to_opening', 14, 2)->default(0)->after('opening_receivable');
        });

        $settlements = DB::table('salesman_settlements')
            ->orderByDesc('id')
            ->get()
            ->unique('salesman_id');

        foreach ($settlements as $settlement) {
            $salesman = DB::table('salesmen')->where('id', $settlement->salesman_id)->first();
            if (! $salesman) {
                continue;
            }

            $leftover = round((float) $settlement->opening_advance + (float) $settlement->cash_received - (float) $settlement->allocated_amount, 2);
            $opening = round((float) $salesman->opening_balance, 2);
            if ($leftover <= 0.009 || $opening <= 0.009) {
                continue;
            }
            if (abs((float) $salesman->advance_balance - (float) $settlement->closing_advance) > 0.009) {
                continue;
            }

            $applied = round(min($leftover, $opening), 2);
            $closing = round($leftover - $applied, 2);

            DB::table('salesman_settlements')->where('id', $settlement->id)->update([
                'opening_receivable' => $opening,
                'applied_to_opening' => $applied,
                'closing_advance' => $closing,
            ]);

            DB::table('salesmen')->where('id', $salesman->id)->update([
                'opening_balance' => round($opening - $applied, 2),
                'advance_balance' => $closing,
            ]);

            if ($settlement->cash_voucher_id) {
                DB::table('cash_vouchers')->where('id', $settlement->cash_voucher_id)->update([
                    'notes' => 'Cash received from salesman '.$salesman->name
                        .' · allocated '.number_format((float) $settlement->allocated_amount, 2)
                        .' · applied to opening balance '.number_format($applied, 2)
                        .' · advance '.number_format($closing, 2),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('salesman_settlements', function (Blueprint $table) {
            $table->dropColumn(['opening_receivable', 'applied_to_opening']);
        });
    }
};
