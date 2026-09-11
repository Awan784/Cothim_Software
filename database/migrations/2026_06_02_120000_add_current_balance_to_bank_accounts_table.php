<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->decimal('current_balance', 14, 2)->default(0)->after('opening_balance');
        });

        // Backfill for existing rows.
        DB::statement('UPDATE bank_accounts SET current_balance = opening_balance WHERE current_balance = 0');
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('current_balance');
        });
    }
};

