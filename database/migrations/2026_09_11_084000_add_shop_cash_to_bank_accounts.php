<?php

use App\Models\BankAccount;
use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->boolean('is_cash')->default(false)->after('is_active');
        });

        Organization::query()->each(function (Organization $organization) {
            $previous = app()->bound('current_organization_id') ? app('current_organization_id') : null;
            app()->instance('current_organization_id', $organization->id);

            try {
                BankAccount::ensureShopCash();
            } finally {
                if ($previous) {
                    app()->instance('current_organization_id', $previous);
                } else {
                    app()->forgetInstance('current_organization_id');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('is_cash');
        });
    }
};
