<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_platform_admin')->default(false)->after('user_type');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('whatsapp', 32)->nullable()->after('vat_number');
            $table->text('admin_notes')->nullable()->after('status');
        });

        $firstAdmin = DB::table('users')->orderBy('id')->value('id');
        if ($firstAdmin) {
            DB::table('users')->where('id', $firstAdmin)->update(['is_platform_admin' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_platform_admin');
        });
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'admin_notes']);
        });
    }
};
