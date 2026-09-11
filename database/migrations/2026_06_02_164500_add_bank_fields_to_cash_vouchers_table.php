<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_vouchers', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('cash_account_id'); // cash, bank
            $table->foreignId('bank_account_id')->nullable()->after('payment_method')
                ->constrained('bank_accounts')->cascadeOnUpdate()->restrictOnDelete();

            $table->index(['payment_method', 'voucher_date']);
            $table->index(['bank_account_id', 'voucher_date']);
        });
    }

    public function down(): void
    {
        Schema::table('cash_vouchers', function (Blueprint $table) {
            $table->dropIndex(['payment_method', 'voucher_date']);
            $table->dropIndex(['bank_account_id', 'voucher_date']);
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn('payment_method');
        });
    }
};

