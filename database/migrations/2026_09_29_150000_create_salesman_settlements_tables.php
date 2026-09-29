<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salesmen', function (Blueprint $table) {
            $table->decimal('advance_balance', 14, 2)->default(0)->after('commission_percent');
        });

        Schema::create('salesman_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('salesman_id')->constrained('salesmen')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('settlement_no');
            $table->date('settlement_date');
            $table->decimal('cash_received', 14, 2)->default(0);
            $table->decimal('allocated_amount', 14, 2)->default(0);
            $table->decimal('opening_advance', 14, 2)->default(0);
            $table->decimal('closing_advance', 14, 2)->default(0);
            $table->string('payment_method')->default('cash');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->unsignedBigInteger('cash_voucher_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'settlement_no']);
            $table->index(['salesman_id', 'settlement_date']);
        });

        Schema::create('salesman_settlement_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('salesman_settlement_id')->constrained('salesman_settlements')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('cash_voucher_id')->nullable()->constrained('cash_vouchers')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });

        Schema::table('cash_vouchers', function (Blueprint $table) {
            $table->boolean('affects_cash')->default(true)->after('notes');
            $table->foreignId('salesman_settlement_id')->nullable()->after('affects_cash')
                ->constrained('salesman_settlements')->nullOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->after('salesman_settlement_id')
                ->constrained('sales_invoices')->nullOnDelete();
        });

        Schema::table('salesman_settlements', function (Blueprint $table) {
            $table->foreign('cash_voucher_id')
                ->references('id')
                ->on('cash_vouchers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('salesman_settlements', function (Blueprint $table) {
            $table->dropForeign(['cash_voucher_id']);
        });

        Schema::table('cash_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_invoice_id');
            $table->dropConstrainedForeignId('salesman_settlement_id');
            $table->dropColumn('affects_cash');
        });

        Schema::dropIfExists('salesman_settlement_allocations');
        Schema::dropIfExists('salesman_settlements');

        Schema::table('salesmen', function (Blueprint $table) {
            $table->dropColumn('advance_balance');
        });
    }
};
