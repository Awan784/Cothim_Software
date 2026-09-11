<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $groups = DB::table('purchase_orders')
            ->orderBy('organization_id')
            ->orderBy('id')
            ->get(['id', 'organization_id', 'po_no'])
            ->groupBy('organization_id');

        foreach ($groups as $orders) {
            $n = 1001;

            foreach ($orders as $order) {
                $temp = 'PO-tmp-'.$order->id;
                DB::table('purchase_orders')->where('id', $order->id)->update(['po_no' => $temp]);
            }

            foreach ($orders as $order) {
                $newNo = 'PO-'.$n;
                $oldNo = $order->po_no;

                DB::table('purchase_orders')->where('id', $order->id)->update(['po_no' => $newNo]);

                DB::table('stock_movements')
                    ->where('source_type', 'purchase_order')
                    ->where('source_id', $order->id)
                    ->where('reference', $oldNo)
                    ->update(['reference' => $newNo]);

                $n++;
            }
        }
    }

    public function down(): void
    {
        // Sequential numbers are the desired format; previous timestamp numbers are not restored.
    }
};
