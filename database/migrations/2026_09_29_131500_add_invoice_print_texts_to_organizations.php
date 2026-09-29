<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->text('print_warranty')->nullable()->after('whatsapp');
            $table->text('print_note')->nullable()->after('print_warranty');
            $table->string('print_on_behalf', 255)->nullable()->after('print_note');
        });

        $warrantyTpl = (string) config('ams.print_warranty');
        $note = (string) config('ams.print_note');
        $onBehalfTpl = (string) config('ams.print_on_behalf');

        foreach (DB::table('organizations')->select('id', 'name')->get() as $org) {
            $company = strtoupper((string) $org->name);
            DB::table('organizations')->where('id', $org->id)->update([
                'print_warranty' => str_replace('{company}', $company, $warrantyTpl),
                'print_note' => $note,
                'print_on_behalf' => str_replace('{company}', $company, $onBehalfTpl),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['print_warranty', 'print_note', 'print_on_behalf']);
        });
    }
};
