<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('party_type', 20)->default('supplier')->after('po_no');
            $table->foreignId('vendor_id')->nullable()->after('supplier_id')->constrained('vendors')->restrictOnDelete();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable()->change();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnUpdate()->restrictOnDelete();
        });

        $users = DB::table('users')->whereNotNull('permissions')->get(['id', 'permissions']);
        foreach ($users as $user) {
            $permissions = json_decode((string) $user->permissions, true);
            if (! is_array($permissions) || ! isset($permissions['suppliers']) || isset($permissions['vendors'])) {
                continue;
            }
            $permissions['vendors'] = $permissions['suppliers'];
            DB::table('users')->where('id', $user->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
        });

        DB::table('purchase_orders')->whereNull('supplier_id')->delete();

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable(false)->change();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnUpdate()->restrictOnDelete();
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropColumn('party_type');
        });

        Schema::dropIfExists('vendors');
    }
};
