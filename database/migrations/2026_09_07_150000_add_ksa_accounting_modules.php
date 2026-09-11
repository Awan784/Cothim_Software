<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('vat_number', 32)->nullable()->after('email');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('vat_number', 32)->nullable()->after('email');
        });

        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->nullable()->unique();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('type', 20)->default('simplified');
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('zatca_qr_payload')->nullable();
            $table->string('zatca_status', 30)->default('none');
            $table->string('zatca_uuid')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sales_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 14, 3)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(15);
            $table->decimal('line_net', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no')->nullable()->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_bill_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_bill_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 14, 3)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(15);
            $table->decimal('line_net', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('inbox_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->default('receipt');
            $table->string('status', 20)->default('new');
            $table->string('title');
            $table->string('original_name')->nullable();
            $table->string('path')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->decimal('extracted_amount', 14, 2)->nullable();
            $table->date('extracted_date')->nullable();
            $table->string('extracted_party')->nullable();
            $table->text('notes')->nullable();
            $table->string('posted_type')->nullable();
            $table->unsignedBigInteger('posted_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        DB::table('app_settings')->insert([
            ['key' => 'company_name', 'value' => config('ams.company_name', 'Accounts'), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'company_vat_number', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'company_cr_number', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'company_address', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'default_vat_rate', 'value' => '15', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'plan', 'value' => 'trial', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'trial_ends_at', 'value' => now()->addDays(14)->toDateString(), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'zatca_environment', 'value' => 'sandbox', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_items');
        Schema::dropIfExists('purchase_bill_lines');
        Schema::dropIfExists('purchase_bills');
        Schema::dropIfExists('sales_invoice_lines');
        Schema::dropIfExists('sales_invoices');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('vat_number');
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('vat_number');
        });

        Schema::dropIfExists('app_settings');
    }
};
