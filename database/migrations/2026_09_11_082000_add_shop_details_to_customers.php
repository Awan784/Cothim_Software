<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('proprietor_name')->nullable()->after('company_name');
            $table->string('ntn', 50)->nullable()->after('email');
            $table->string('strn', 50)->nullable()->after('ntn');
            $table->string('license_no', 100)->nullable()->after('strn');
            $table->string('mobile', 50)->nullable()->after('phone');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('area', 150)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'proprietor_name',
                'ntn',
                'strn',
                'license_no',
                'mobile',
                'city',
                'area',
            ]);
        });
    }
};
