<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category_code', 100)->nullable()->after('code');
            $table->string('supplier_code', 50)->nullable()->index()->after('unit');
            $table->string('brand', 255)->nullable()->after('supplier_code');
            $table->string('other_name', 255)->nullable()->after('brand');
        });

        // Backfill category_code from category if available
        if (Schema::hasColumn('products', 'category')) {
            DB::table('products')
                ->whereNull('category_code')
                ->whereNotNull('category')
                ->update(['category_code' => DB::raw('category')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['supplier_code']);
            $table->dropColumn(['category_code', 'supplier_code', 'brand', 'other_name']);
        });
    }
};
