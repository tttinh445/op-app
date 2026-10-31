<?php

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;

test('orders index page can be rendered with tabs and table columns', function () {
    Order::factory()->count(3)->create();

    $response = $this->get(route('orders.index'));

    $response->assertStatus(200);
    $response->assertSee('Đơn hàng');
    $response->assertSee('Hợp đồng');
    $response->assertSee('Thêm Đơn hàng+');
    $response->assertSee('Tổng tiền');
    $response->assertSee('Thanh toán Sale');
});

test('orders are sorted descending by order_date by default', function () {
    $orderOld = Order::factory()->create([
        'code' => 'ORD-OLD',
        'order_date' => now()->subDays(5),
    ]);
    $orderNew = Order::factory()->create([
        'code' => 'ORD-NEW',
        'order_date' => now(),
    ]);

    $response = $this->get(route('orders.index'));

    $response->assertStatus(200);
    $response->assertSeeInOrder(['ORD-NEW', 'ORD-OLD']);
});

test('orders are paginated at 30 items per page', function () {
    Order::factory()->count(35)->create();

    $response = $this->get(route('orders.index'));

    $response->assertStatus(200);
    $this->assertCount(30, $response->viewData('orders'));
    expect($response->viewData('orders')->total())->toBe(35);
});

test('can filter orders by code, customer, salesperson, and sale_payment_status', function () {
    $sale = Employee::factory()->create(['name' => 'Nguyễn Sale VIP']);
    $order1 = Order::factory()->create([
        'code' => 'ORD-FILTER-TARGET',
        'customer' => 'CUST-TARGET',
        'salesperson' => $sale->name,
        'sale_payment_status' => 'Đã thanh toán',
    ]);
    $order2 = Order::factory()->create([
        'code' => 'ORD-OTHER',
        'customer' => 'CUST-OTHER',
        'salesperson' => 'Other Person',
        'sale_payment_status' => 'Chưa thanh toán',
    ]);

    // Filter by code
    $responseCode = $this->get(route('orders.index', ['code' => 'TARGET']));
    $responseCode->assertSee('ORD-FILTER-TARGET');
    $responseCode->assertDontSee('ORD-OTHER');

    // Filter by customer
    $responseCustomer = $this->get(route('orders.index', ['customer' => 'TARGET']));
    $responseCustomer->assertSee('ORD-FILTER-TARGET');
    $responseCustomer->assertDontSee('ORD-OTHER');

    // Filter by salesperson
    $responseSale = $this->get(route('orders.index', ['salesperson' => $sale->name]));
    $responseSale->assertSee('ORD-FILTER-TARGET');
    $responseSale->assertDontSee('ORD-OTHER');

    // Filter by sale_payment_status
    $responsePayment = $this->get(route('orders.index', ['sale_payment_status' => 'Đã thanh toán']));
    $responsePayment->assertSee('ORD-FILTER-TARGET');
    $responsePayment->assertDontSee('ORD-OTHER');
});

test('can create a new order with valid data', function () {
    $orderData = [
        'code' => 'ORD-TEST-001',
        'order_date' => now()->format('Y-m-d H:i:s'),
        'name' => 'Dự án Cải tạo Văn phòng',
        'customer' => 'CUST-VN-001',
        'type' => 'Dự án',
        'total_amount' => 15000000,
        'status' => 'Mới',
        'quote_url' => 'https://example.com/quote-001',
        'note' => 'Ghi chú đơn hàng thử nghiệm',
        'salesperson' => 'Quy',
        'sale_discount' => 500000,
        'sale_payment_status' => 'Chưa thanh toán',
    ];

    $response = $this->post(route('orders.store'), $orderData);

    $response->assertRedirect(route('orders.index'));
    $this->assertDatabaseHas('orders', [
        'code' => 'ORD-TEST-001',
        'customer' => 'CUST-VN-001',
        'type' => 'Dự án',
        'total_amount' => 15000000,
        'salesperson' => 'Quy',
    ]);
});

test('can create order with custom customer not in database', function () {
    $orderData = [
        'code' => 'ORD-CUSTOM-CUST',
        'order_date' => now()->format('Y-m-d H:i:s'),
        'customer' => 'Công ty TNHH Ngoại Lệ Chưa Có Mã',
        'type' => 'Đơn hàng',
        'total_amount' => 5000000,
        'status' => 'Mới',
        'sale_payment_status' => 'Chưa thanh toán',
    ];

    $response = $this->post(route('orders.store'), $orderData);

    $response->assertRedirect(route('orders.index'));
    $this->assertDatabaseHas('orders', [
        'code' => 'ORD-CUSTOM-CUST',
        'customer' => 'Công ty TNHH Ngoại Lệ Chưa Có Mã',
    ]);
});

test('validation fails when required order fields are missing or code is duplicate', function () {
    $existing = Order::factory()->create(['code' => 'ORD-EXISTS']);

    $response = $this->post(route('orders.store'), [
        'code' => 'ORD-EXISTS',
        'order_date' => '',
        'customer' => '',
        'type' => 'Invalid Type',
        'status' => 'Invalid Status',
        'sale_payment_status' => 'Invalid Payment',
    ]);

    $response->assertSessionHasErrors(['code', 'order_date', 'customer', 'type', 'status', 'sale_payment_status']);
});

test('check code duplicate endpoint for orders works properly', function () {
    $order = Order::factory()->create(['code' => 'ORD-UNIQUE-101']);

    // Check duplicate
    $resDup = $this->getJson(route('orders.check_code', ['code' => 'ORD-UNIQUE-101']));
    $resDup->assertOk()->assertJson(['exists' => true]);

    // Check available
    $resAvail = $this->getJson(route('orders.check_code', ['code' => 'ORD-FREE-999']));
    $resAvail->assertOk()->assertJson(['exists' => false]);

    // Check ignoring self
    $resIgnore = $this->getJson(route('orders.check_code', ['code' => 'ORD-UNIQUE-101', 'ignore_id' => $order->id]));
    $resIgnore->assertOk()->assertJson(['exists' => false]);
});

test('can show order in json format', function () {
    $order = Order::factory()->create(['code' => 'ORD-SHOW-TEST']);

    $response = $this->getJson(route('orders.show', $order));

    $response->assertOk();
    $response->assertJsonPath('data.code', 'ORD-SHOW-TEST');
});

test('can update an existing order', function () {
    $order = Order::factory()->create([
        'code' => 'ORD-UPDATE-OLD',
        'total_amount' => 1000000,
        'status' => 'Mới',
    ]);

    $response = $this->put(route('orders.update', $order), [
        'code' => 'ORD-UPDATE-NEW',
        'order_date' => now()->format('Y-m-d H:i:s'),
        'customer' => 'CUST-NEW-CODE',
        'type' => 'Đơn hàng',
        'total_amount' => 2500000,
        'status' => 'Đang giao hàng',
        'sale_payment_status' => 'Đã thanh toán',
    ]);

    $response->assertRedirect(route('orders.index'));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'code' => 'ORD-UPDATE-NEW',
        'customer' => 'CUST-NEW-CODE',
        'total_amount' => 2500000,
        'status' => 'Đang giao hàng',
        'sale_payment_status' => 'Đã thanh toán',
    ]);
});

test('can delete an order', function () {
    $order = Order::factory()->create(['code' => 'ORD-TO-DELETE']);

    $response = $this->delete(route('orders.destroy', $order));

    $response->assertRedirect(route('orders.index'));
    $this->assertDatabaseMissing('orders', ['id' => $order->id]);
});

test('order sheetHeaders and toSheetRow match expected 13 columns', function () {
    $headers = Order::sheetHeaders();

    expect($headers)->toBe([
        'Dấu thời gian',
        'Ngày',
        'Mã',
        'Tên đơn/dự án',
        'Khách hàng',
        'Loại',
        'Tổng tiền',
        'Trạng Thái',
        'Link báo giá',
        'Ghi chú',
        'NV Sale',
        'Chiết khấu NV sale',
        'Thanh toán cho sale',
    ]);
    expect(count($headers))->toBe(13);

    $order = Order::factory()->create([
        'code' => 'ORD-ROW-TEST',
        'order_date' => '2026-10-31 10:00:00',
        'name' => 'Tên Dự án Test',
        'customer' => 'KH-001',
        'type' => 'Dự án',
        'total_amount' => 12500000,
        'status' => 'Đang thi công',
        'quote_url' => 'https://example.com/quote',
        'note' => 'Ghi chú đơn',
        'salesperson' => 'NV-01',
        'sale_discount' => 300000,
        'sale_payment_status' => 'Đã thanh toán',
    ]);

    $row = $order->toSheetRow();

    expect(count($row))->toBe(13);
    expect($row[1])->toBe('2026-10-31 10:00:00');
    expect($row[2])->toBe('ORD-ROW-TEST');
    expect($row[3])->toBe('Tên Dự án Test');
    expect($row[4])->toBe('KH-001');
    expect($row[5])->toBe('Dự án');
    expect($row[6])->toBe(12500000.0);
    expect($row[7])->toBe('Đang thi công');
    expect($row[8])->toBe('https://example.com/quote');
    expect($row[9])->toBe('Ghi chú đơn');
    expect($row[10])->toBe('NV-01');
    expect($row[11])->toBe(300000.0);
    expect($row[12])->toBe('Đã thanh toán');
});

test('order fromSheetRow maps row array to attributes correctly by index', function () {
    $row = [
        '2026-10-31 08:00:00', // 0: Dấu thời gian
        '2026-10-30 09:30:00', // 1: Ngày
        'ORD-IMPORT-001',      // 2: Mã
        'Tên Đơn Nhập Từ Sheet', // 3: Tên đơn/dự án
        'KH-SHEET-01',         // 4: Khách hàng
        'Đơn hàng',            // 5: Loại
        '5,000,000 ₫',         // 6: Tổng tiền
        'Hoàn thành',          // 7: Trạng Thái
        'https://quote.link',  // 8: Link báo giá
        'Ghi chú nhập',        // 9: Ghi chú
        'Nguyễn Sale',         // 10: NV Sale
        '200,000',             // 11: Chiết khấu NV sale
        'Đã thanh toán',       // 12: Thanh toán cho sale
    ];

    $attr = Order::fromSheetRow($row);

    expect($attr['code'])->toBe('ORD-IMPORT-001');
    expect($attr['name'])->toBe('Tên Đơn Nhập Từ Sheet');
    expect($attr['customer'])->toBe('KH-SHEET-01');
    expect($attr['type'])->toBe('Đơn hàng');
    expect($attr['total_amount'])->toBe(5000000.0);
    expect($attr['status'])->toBe('Hoàn thành');
    expect($attr['quote_url'])->toBe('https://quote.link');
    expect($attr['note'])->toBe('Ghi chú nhập');
    expect($attr['salesperson'])->toBe('Nguyễn Sale');
    expect($attr['sale_discount'])->toBe(200000.0);
    expect($attr['sale_payment_status'])->toBe('Đã thanh toán');
});

test('orders sync fails gracefully if no active sheet config exists', function () {
    $response = $this->post(route('orders.sync'), ['direction' => 'pull']);

    $response->assertRedirect(route('orders.index'));
    $response->assertSessionHas('error');
});

test('order calculateSaleDiscount follows Google Sheet formula with 10k rounding and 0/5000/10000 thresholds', function () {
    // 55.000.000 / 55 - 3 = 999.997 -> floor10k = 990.000, mod = 9997 > 7000 -> +10.000 = 1.000.000
    expect(Order::calculateSaleDiscount(55000000))->toBe(1000000);

    // calc <= 3000 -> +0
    // 55.165.000 / 55 - 3 = 1.002.997 -> mod 2997 <= 3000 -> 1.000.000
    expect(Order::calculateSaleDiscount(55165000))->toBe(1000000);

    // calc <= 7000 -> +5000
    // 55.275.000 / 55 - 3 = 1.004.997 -> mod 4997 <= 7000 -> 1.005.000
    expect(Order::calculateSaleDiscount(55275000))->toBe(1005000);

    // calc > 7000 -> +10000
    // 55.440.000 / 55 - 3 = 1.007.997 -> mod 7997 > 7000 -> 1.010.000
    expect(Order::calculateSaleDiscount(55440000))->toBe(1010000);

    // User's example: 2390120 calc -> 2390000
    // 131.456.600 / 55 - 3 = 2.390.117 -> mod 117 <= 3000 -> 2.390.000
    expect(Order::calculateSaleDiscount(131456600))->toBe(2390000);

    // 0 or small amount
    expect(Order::calculateSaleDiscount(0))->toBe(0);
    expect(Order::calculateSaleDiscount(100))->toBe(0);
});

test('order controller sanitizes Vietnamese formatted currency strings with dots', function () {
    $orderData = [
        'code' => 'ORD-CURRENCY-01',
        'order_date' => '2026-10-31 10:00:00',
        'name' => 'Đơn kiểm tra tiền tệ',
        'customer' => 'KH-001',
        'type' => 'Đơn hàng',
        'total_amount' => '1.500.000',
        'status' => 'Mới',
        'salesperson' => 'NV-Sale-1',
        'sale_discount' => '30.000',
        'sale_payment_status' => 'Chưa thanh toán',
    ];

    $response = $this->post(route('orders.store'), $orderData);
    $response->assertRedirect(route('orders.index'));

    $order = Order::where('code', 'ORD-CURRENCY-01')->first();
    expect($order)->not->toBeNull();
    expect((float) $order->total_amount)->toBe(1500000.0);
    expect((float) $order->sale_discount)->toBe(30000.0);
});

test('order controller auto-calculates sale_discount when not supplied', function () {
    $orderData = [
        'code' => 'ORD-AUTO-DISCOUNT',
        'order_date' => '2026-10-31 10:00:00',
        'customer' => 'KH-001',
        'type' => 'Đơn hàng',
        'total_amount' => '55.000.000',
        'status' => 'Mới',
        'sale_payment_status' => 'Chưa thanh toán',
    ];

    $response = $this->post(route('orders.store'), $orderData);
    $response->assertRedirect(route('orders.index'));

    $order = Order::where('code', 'ORD-AUTO-DISCOUNT')->first();
    expect($order)->not->toBeNull();
    // 55.000.000 -> 1.000.000
    expect((float) $order->sale_discount)->toBe(1000000.0);
});

test('order quote_url is normalized with https prefix when domain without scheme is entered', function () {
    $orderData = [
        'code' => 'ORD-URL-NORM',
        'order_date' => '2026-10-31 10:00:00',
        'customer' => 'KH-001',
        'type' => 'Đơn hàng',
        'total_amount' => 1000000,
        'status' => 'Mới',
        'sale_payment_status' => 'Chưa thanh toán',
        'quote_url' => 'drive.google.com/file/d/12345/view',
    ];

    $response = $this->post(route('orders.store'), $orderData);
    $response->assertRedirect(route('orders.index'));

    $order = Order::where('code', 'ORD-URL-NORM')->first();
    expect($order)->not->toBeNull();
    expect($order->quote_url)->toBe('https://drive.google.com/file/d/12345/view');
});

test('order quote_url validation fails on invalid url format', function () {
    $orderData = [
        'code' => 'ORD-URL-FAIL',
        'order_date' => '2026-10-31 10:00:00',
        'customer' => 'KH-001',
        'type' => 'Đơn hàng',
        'total_amount' => 1000000,
        'status' => 'Mới',
        'sale_payment_status' => 'Chưa thanh toán',
        'quote_url' => 'invalid-not-a-url',
    ];

    $response = $this->post(route('orders.store'), $orderData);
    $response->assertSessionHasErrors(['quote_url']);
});
