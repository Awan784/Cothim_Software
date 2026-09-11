<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->string('type'); // receive, payment
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->cascadeOnUpdate()->restrictOnDelete();

            $table->string('account_type'); // customer, supplier, investor, other
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('other_name')->nullable();

            $table->decimal('amount', 14, 2);
            $table->date('voucher_date');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['account_type', 'account_id']);
            $table->index(['cash_account_id', 'voucher_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_vouchers');
    }
};
