<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->decimal('company_retain_percent', 5, 2)->default(50)->after('default_vat_rate');
            $table->decimal('salesman_commission_percent', 5, 2)->default(25)->after('company_retain_percent');
        });

        Schema::table('salesmen', function (Blueprint $table) {
            $table->decimal('commission_percent', 5, 2)->nullable()->after('monthly_target');
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('order_no');
            $table->foreignId('salesman_id')->constrained('salesmen')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('order_date');
            $table->string('status', 20)->default('pending');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('company_retain_percent', 5, 2)->default(50);
            $table->decimal('salesman_commission_percent', 5, 2)->default(25);
            $table->decimal('company_retain_amount', 14, 2)->default(0);
            $table->decimal('salesman_commission_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'order_no']);
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('stock_item_id')->nullable()->constrained('stock_items')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 14, 3)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(15);
            $table->decimal('line_net', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('salesman_id')->nullable()->after('customer_id')->constrained('salesmen')->nullOnDelete();
            $table->foreignId('sales_order_id')->nullable()->after('salesman_id')->constrained('sales_orders')->nullOnDelete();
            $table->decimal('company_retain_percent', 5, 2)->default(0)->after('total');
            $table->decimal('salesman_commission_percent', 5, 2)->default(0)->after('company_retain_percent');
            $table->decimal('company_retain_amount', 14, 2)->default(0)->after('salesman_commission_percent');
            $table->decimal('salesman_commission_amount', 14, 2)->default(0)->after('company_retain_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_order_id');
            $table->dropConstrainedForeignId('salesman_id');
            $table->dropColumn([
                'company_retain_percent',
                'salesman_commission_percent',
                'company_retain_amount',
                'salesman_commission_amount',
            ]);
        });

        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');

        Schema::table('salesmen', function (Blueprint $table) {
            $table->dropColumn('commission_percent');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['company_retain_percent', 'salesman_commission_percent']);
        });
    }
};
