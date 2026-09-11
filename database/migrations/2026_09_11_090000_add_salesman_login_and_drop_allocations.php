<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salesmen', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
            $table->string('password')->nullable()->after('username');
            $table->string('show_password')->nullable()->after('password');
            $table->unique(['organization_id', 'username']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salesman_id');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salesman_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('salesman_id')
                ->nullable()
                ->after('area')
                ->constrained('salesmen')
                ->nullOnDelete();
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('salesman_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('salesmen')
                ->nullOnDelete();
        });

        Schema::table('salesmen', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'username']);
            $table->dropColumn(['username', 'password', 'show_password']);
        });
    }
};
