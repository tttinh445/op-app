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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Mã: String, không trùng, không trống
            $table->string('contract_number')->nullable(); // Số HĐ: String, được phép trống
            $table->dateTime('contract_date'); // Ngày: date time
            $table->string('partner'); // Đối tác: String, không trống (mã KH hoặc tên mới)
            $table->decimal('amount', 15, 2)->default(0); // Số tiền: Number
            $table->string('status')->default('Mới'); // Trạng Thái: 'Mới', 'Đã tạo HD online', 'Đã ký Online', 'Đã ký tay'
            $table->string('type')->default('Bán hàng'); // Loại: 'Bán hàng', 'Mua hàng'
            $table->string('email')->nullable(); // Email: String
            $table->string('link')->nullable(); // Link: String URL, được phép trống
            $table->string('quote_link')->nullable(); // Link báo giá: String URL, được phép trống
            $table->text('note')->nullable(); // Ghi chú: Text, được phép trống
            $table->string('salesperson')->nullable(); // NV Sale: String (lưu name từ employees)
            $table->decimal('sale_discount', 15, 2)->nullable(); // Chiết khấu NV Sale: Number
            $table->string('sale_payment_status')->default('Chưa thanh toán'); // Thanh toán cho Sale: 'Chưa thanh toán', 'Đã thanh toán'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
