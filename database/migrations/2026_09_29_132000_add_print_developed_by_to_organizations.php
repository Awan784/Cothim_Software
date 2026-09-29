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
            $table->string('print_developed_by', 255)->nullable()->after('print_on_behalf');
        });

        DB::table('organizations')->update([
            'print_developed_by' => (string) config('ams.print_developed_by'),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('print_developed_by');
        });
    }
};
