<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $vouchers = DB::table('cash_vouchers')
            ->orderBy('organization_id')
            ->orderBy('id')
            ->get(['id', 'organization_id', 'type', 'voucher_no']);

        foreach ($vouchers as $voucher) {
            DB::table('cash_vouchers')->where('id', $voucher->id)->update([
                'voucher_no' => 'CV-tmp-'.$voucher->id,
            ]);
        }

        $grouped = $vouchers->groupBy(fn ($row) => $row->organization_id.'|'.$row->type);

        foreach ($grouped as $rows) {
            $type = $rows->first()->type;
            $prefix = $type === 'receive' ? 'CRV' : 'CPV';
            $n = 101;

            foreach ($rows as $voucher) {
                DB::table('cash_vouchers')->where('id', $voucher->id)->update([
                    'voucher_no' => $prefix.'-'.$n,
                ]);
                $n++;
            }
        }
    }

    public function down(): void
    {
        // Sequential CPV/CRV numbers are the desired format.
    }
};
