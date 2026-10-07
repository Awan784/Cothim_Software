<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salesmen', function (Blueprint $table) {
            $table->json('cities')->nullable()->after('city');
        });

        $rows = DB::table('salesmen')->select('id', 'city')->get();
        foreach ($rows as $row) {
            $city = trim((string) ($row->city ?? ''));
            DB::table('salesmen')->where('id', $row->id)->update([
                'cities' => $city === '' ? null : json_encode([$city]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('salesmen', function (Blueprint $table) {
            $table->dropColumn('cities');
        });
    }
};
