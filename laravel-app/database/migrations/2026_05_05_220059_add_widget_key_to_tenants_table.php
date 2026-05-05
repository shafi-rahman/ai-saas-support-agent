<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('widget_key', 64)->unique()->nullable()->after('slug');
        });

        // Generate keys for any existing tenants
        DB::table('tenants')->whereNull('widget_key')->get()->each(function ($tenant) {
            DB::table('tenants')->where('id', $tenant->id)
                ->update(['widget_key' => bin2hex(random_bytes(16))]);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('widget_key');
        });
    }
};
