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
        // 1. Add sync_status to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->string('sync_status')->default('synced')->after('synced_at'); // 'synced', 'pending_create', 'pending_update'
        });

        // 2. Add sync_status to suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('sync_status')->default('synced')->after('synced_at');
        });

        // 3. Add sync_status to products
        Schema::table('products', function (Blueprint $table) {
            $table->string('sync_status')->default('synced')->after('synced_at');
        });

        // 4. Create pending_sheet_syncs table to track delete actions and sync history
        Schema::create('pending_sheet_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('sheet_type'); // 'customer', 'supplier', 'product'
            $table->string('action'); // 'create', 'update', 'delete'
            $table->string('code'); // Mã đối tượng
            $table->string('original_code')->nullable(); // Mã cũ nếu có
            $table->string('name')->nullable(); // Tên đối tượng để hiển thị trên UI
            $table->foreignId('google_sheet_config_id')->nullable()->constrained('google_sheet_configs')->nullOnDelete();
            $table->json('row_data')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'synced', 'failed'
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_sheet_syncs');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('sync_status');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('sync_status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sync_status');
        });
    }
};
