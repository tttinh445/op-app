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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->dateTime('order_date'); // Ngày: date time
            $table->string('code')->unique(); // Mã: String, không trùng, không trống
            $table->string('name')->nullable(); // Tên đơn/dự án: String, cho phép trống
            $table->string('customer'); // Khách hàng: String, không trống (mã KH hoặc tên mới)
            $table->string('type')->default('Đơn hàng'); // Loại: 'Đơn hàng', 'Dự án'
            $table->decimal('total_amount', 15, 2)->default(0); // Tổng tiền: Number
            $table->string('status')->default('Mới'); // Trạng Thái: 'Mới', 'Đang xử lý', 'Đang giao hàng', 'Hoàn thành', 'Đã giao hàng', 'Đang thi công'
            $table->string('quote_url')->nullable(); // Link báo giá: url
            $table->text('note')->nullable(); // Ghi chú: Text
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
        Schema::dropIfExists('orders');
    }
};
