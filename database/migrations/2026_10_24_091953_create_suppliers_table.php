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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Mã (bắt buộc, không trùng)
            $table->string('name'); // Tên NPP/NCC (bắt buộc)
            $table->string('type'); // Loại: 'NPP', 'NCC' (bắt buộc)
            $table->string('zalo')->nullable(); // Zalo
            $table->string('tax_id')->unique(); // MST (bắt buộc, không trùng)
            $table->string('bank_account')->nullable(); // STK
            $table->string('bank_name')->nullable(); // Ngân hàng
            $table->text('address')->nullable(); // Địa chỉ
            $table->string('phone')->nullable(); // Điện thoại
            $table->string('email')->nullable(); // Email
            $table->string('website')->nullable(); // Website
            $table->string('facebook')->nullable(); // FB
            $table->text('note')->nullable(); // Ghi chú
            $table->foreignId('google_sheet_config_id')->nullable()->constrained('google_sheet_configs')->nullOnDelete();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
