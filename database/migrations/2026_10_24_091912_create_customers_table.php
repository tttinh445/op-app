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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Mã (bắt buộc, không trùng)
            $table->string('name'); // Tên khách hàng (bắt buộc)
            $table->string('type'); // Loại: 'Khách vãng lai', 'Công ty' (bắt buộc)
            $table->string('tax_id')->unique(); // MST (bắt buộc, không trùng)
            $table->string('phone')->nullable(); // Điện thoại
            $table->string('email')->nullable(); // Email
            $table->string('bank_account')->nullable(); // Số TK
            $table->string('bank_name')->nullable(); // Ngân hàng
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
        Schema::dropIfExists('customers');
    }
};
