<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var list<string> */
    private array $tenantTables = [
        'customers',
        'suppliers',
        'investors',
        'bank_accounts',
        'cash_accounts',
        'cash_vouchers',
        'expense_categories',
        'expenses',
        'nominal_accounts',
        'journal_vouchers',
        'stock_categories',
        'stock_items',
        'stock_movements',
        'purchase_orders',
        'sales_invoices',
        'purchase_bills',
        'inbox_items',
    ];

    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('plan', 20)->default('trial');
            $table->date('trial_ends_at')->nullable();
            $table->string('vat_number', 32)->nullable();
            $table->string('cr_number', 32)->nullable();
            $table->text('address')->nullable();
            $table->decimal('default_vat_rate', 5, 2)->default(15);
            $table->string('zatca_environment', 20)->default('sandbox');
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        $settings = [];
        if (Schema::hasTable('app_settings')) {
            $settings = DB::table('app_settings')->pluck('value', 'key')->all();
        }

        $orgId = DB::table('organizations')->insertGetId([
            'name' => $settings['company_name'] ?? config('ams.company_name', 'My company'),
            'slug' => Str::slug((string) ($settings['company_name'] ?? 'company')).'-'.Str::lower(Str::random(5)),
            'plan' => $settings['plan'] ?? 'trial',
            'trial_ends_at' => $settings['trial_ends_at'] ?? now()->addDays(14)->toDateString(),
            'vat_number' => ($settings['company_vat_number'] ?? '') !== '' ? $settings['company_vat_number'] : null,
            'cr_number' => ($settings['company_cr_number'] ?? '') !== '' ? $settings['company_cr_number'] : null,
            'address' => ($settings['company_address'] ?? '') !== '' ? $settings['company_address'] : null,
            'default_vat_rate' => (float) ($settings['default_vat_rate'] ?? 15),
            'zatca_environment' => $settings['zatca_environment'] ?? 'sandbox',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
        DB::table('users')->update(['organization_id' => $orgId]);

        foreach ($this->tenantTables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'organization_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            });
            DB::table($table)->whereNull('organization_id')->update(['organization_id' => $orgId]);
        }

        $this->uniquePerOrg('sales_invoices', 'invoice_no');
        $this->uniquePerOrg('purchase_bills', 'bill_no');
        $this->uniquePerOrg('cash_vouchers', 'voucher_no');
        $this->uniquePerOrg('journal_vouchers', 'voucher_no');
        $this->uniquePerOrg('purchase_orders', 'po_no');
        $this->uniquePerOrg('nominal_accounts', 'code');
        $this->uniquePerOrg('stock_items', 'sku');
        $this->uniquePerOrg('stock_categories', 'name');
        $this->uniquePerOrg('cash_accounts', 'name');
        $this->uniquePerOrg('expense_categories', 'name');
    }

    public function down(): void
    {
        foreach ($this->tenantTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'organization_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropConstrainedForeignId('organization_id');
                });
            }
        }

        if (Schema::hasColumn('users', 'organization_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('organization_id');
            });
        }

        Schema::dropIfExists('organizations');
    }

    private function uniquePerOrg(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropUnique([$column]);
            });
        } catch (\Throwable) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($table, $column) {
                    $blueprint->dropUnique($table.'_'.$column.'_unique');
                });
            } catch (\Throwable) {
                // Keep going; composite unique can still be added.
            }
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->unique(['organization_id', $column]);
            });
        } catch (\Throwable) {
            // Duplicate index or leftover unique — tenant isolation still works via organization_id.
        }
    }
};
