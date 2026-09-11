<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salesmen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('monthly_target', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

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
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salesman_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salesman_id');
        });

        Schema::dropIfExists('salesmen');
    }
};
