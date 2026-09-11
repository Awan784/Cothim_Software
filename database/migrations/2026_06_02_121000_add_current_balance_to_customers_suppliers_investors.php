<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('current_balance', 14, 2)->default(0)->after('opening_balance');
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('current_balance', 14, 2)->default(0)->after('opening_balance');
        });
        Schema::table('investors', function (Blueprint $table) {
            $table->decimal('current_balance', 14, 2)->default(0)->after('opening_investment');
        });

        DB::statement('UPDATE customers SET current_balance = opening_balance WHERE current_balance = 0');
        DB::statement('UPDATE suppliers SET current_balance = opening_balance WHERE current_balance = 0');
        DB::statement('UPDATE investors SET current_balance = opening_investment WHERE current_balance = 0');
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('current_balance');
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('current_balance');
        });
        Schema::table('investors', function (Blueprint $table) {
            $table->dropColumn('current_balance');
        });
    }
};

