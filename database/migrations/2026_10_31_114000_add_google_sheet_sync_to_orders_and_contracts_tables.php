<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add google sheet sync fields to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('google_sheet_config_id')->nullable()->after('sale_payment_status')->constrained('google_sheet_configs')->nullOnDelete();
            $table->timestamp('synced_at')->nullable()->after('google_sheet_config_id');
            $table->string('sync_status')->default('synced')->after('synced_at'); // 'synced', 'pending_create', 'pending_update'
        });

        // 2. Add google sheet sync fields to contracts
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('google_sheet_config_id')->nullable()->after('sale_payment_status')->constrained('google_sheet_configs')->nullOnDelete();
            $table->timestamp('synced_at')->nullable()->after('google_sheet_config_id');
            $table->string('sync_status')->default('synced')->after('synced_at'); // 'synced', 'pending_create', 'pending_update'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['google_sheet_config_id']);
            $table->dropColumn(['google_sheet_config_id', 'synced_at', 'sync_status']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['google_sheet_config_id']);
            $table->dropColumn(['google_sheet_config_id', 'synced_at', 'sync_status']);
        });
    }
};
