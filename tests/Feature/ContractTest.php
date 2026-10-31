<?php

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Employee;

test('contracts index page can be rendered with tabs and table columns', function () {
    Contract::factory()->count(3)->create();

    $response = $this->get(route('contracts.index'));

    $response->assertStatus(200);
    $response->assertSee('Đơn hàng');
    $response->assertSee('Hợp đồng');
    $response->assertSee('Thêm Hợp đồng+');
    $response->assertSee('Số HĐ');
    $response->assertSee('Đối tác');
    $response->assertSee('Số tiền');
    $response->assertSee('Thanh toán Sale');
});

test('contracts are sorted descending by contract_date by default', function () {
    $contractOld = Contract::factory()->create([
        'code' => 'HD-OLD',
        'contract_date' => now()->subDays(5),
    ]);
    $contractNew = Contract::factory()->create([
        'code' => 'HD-NEW',
        'contract_date' => now(),
    ]);

    $response = $this->get(route('contracts.index'));

    $response->assertStatus(200);
    $response->assertSeeInOrder(['HD-NEW', 'HD-OLD']);
});

test('contracts are paginated at 30 items per page', function () {
    Contract::factory()->count(35)->create();

    $response = $this->get(route('contracts.index'));

    $response->assertStatus(200);
    $this->assertCount(30, $response->viewData('contracts'));
    expect($response->viewData('contracts')->total())->toBe(35);
});

test('can filter contracts by code, partner, status, salesperson, and sale_payment_status', function () {
    $sale = Employee::factory()->create(['name' => 'Trần Sale']);
    $contract1 = Contract::factory()->create([
        'code' => 'HD-FILTER-TARGET',
        'partner' => 'PARTNER-TARGET',
        'status' => 'Đã ký Online',
        'salesperson' => $sale->name,
        'sale_payment_status' => 'Đã thanh toán',
    ]);
    $contract2 = Contract::factory()->create([
        'code' => 'HD-OTHER',
        'partner' => 'PARTNER-OTHER',
        'status' => 'Mới',
        'salesperson' => 'Other Guy',
        'sale_payment_status' => 'Chưa thanh toán',
    ]);

    // Filter by code
    $resCode = $this->get(route('contracts.index', ['code' => 'TARGET']));
    $resCode->assertSee('HD-FILTER-TARGET');
    $resCode->assertDontSee('HD-OTHER');

    // Filter by partner
    $resPartner = $this->get(route('contracts.index', ['partner' => 'TARGET']));
    $resPartner->assertSee('HD-FILTER-TARGET');
    $resPartner->assertDontSee('HD-OTHER');

    // Filter by status
    $resStatus = $this->get(route('contracts.index', ['status' => 'Đã ký Online']));
    $resStatus->assertSee('HD-FILTER-TARGET');
    $resStatus->assertDontSee('HD-OTHER');

    // Filter by salesperson
    $resSale = $this->get(route('contracts.index', ['salesperson' => $sale->name]));
    $resSale->assertSee('HD-FILTER-TARGET');
    $resSale->assertDontSee('HD-OTHER');

    // Filter by sale_payment_status
    $resPay = $this->get(route('contracts.index', ['sale_payment_status' => 'Đã thanh toán']));
    $resPay->assertSee('HD-FILTER-TARGET');
    $resPay->assertDontSee('HD-OTHER');
});

test('can create a new contract with valid data', function () {
    $contractData = [
        'code' => 'HD-TEST-001',
        'contract_number' => '01/2026/HDKT',
        'contract_date' => now()->format('Y-m-d H:i:s'),
        'partner' => 'CUST-001',
        'amount' => 50000000,
        'status' => 'Mới',
        'type' => 'Bán hàng',
        'email' => 'doitac@example.com',
        'link' => 'https://example.com/contract-001.pdf',
        'quote_link' => 'https://example.com/quote-001.pdf',
        'note' => 'Hợp đồng mua bán thiết bị văn phòng',
        'salesperson' => 'Quy',
        'sale_discount' => 1000000,
        'sale_payment_status' => 'Chưa thanh toán',
    ];

    $response = $this->post(route('contracts.store'), $contractData);

    $response->assertRedirect(route('contracts.index'));
    $this->assertDatabaseHas('contracts', [
        'code' => 'HD-TEST-001',
        'contract_number' => '01/2026/HDKT',
        'partner' => 'CUST-001',
        'amount' => 50000000,
        'status' => 'Mới',
        'type' => 'Bán hàng',
        'email' => 'doitac@example.com',
    ]);
});

test('can create contract with partner not in database', function () {
    $contractData = [
        'code' => 'HD-CUSTOM-PARTNER',
        'contract_date' => now()->format('Y-m-d H:i:s'),
        'partner' => 'Đối tác Tư nhân Chưa có trong DB',
        'amount' => 12000000,
        'status' => 'Mới',
        'type' => 'Mua hàng',
        'sale_payment_status' => 'Chưa thanh toán',
    ];

    $response = $this->post(route('contracts.store'), $contractData);

    $response->assertRedirect(route('contracts.index'));
    $this->assertDatabaseHas('contracts', [
        'code' => 'HD-CUSTOM-PARTNER',
        'partner' => 'Đối tác Tư nhân Chưa có trong DB',
        'type' => 'Mua hàng',
    ]);
});

test('validation fails when required contract fields are missing or code is duplicate', function () {
    $existing = Contract::factory()->create(['code' => 'HD-EXISTS']);

    $response = $this->post(route('contracts.store'), [
        'code' => 'HD-EXISTS',
        'contract_date' => '',
        'partner' => '',
        'status' => 'Invalid Status',
        'type' => 'Invalid Type',
        'sale_payment_status' => 'Invalid Payment',
    ]);

    $response->assertSessionHasErrors(['code', 'contract_date', 'partner', 'status', 'type', 'sale_payment_status']);
});

test('check code duplicate endpoint for contracts works properly', function () {
    $contract = Contract::factory()->create(['code' => 'HD-UNIQUE-202']);

    // Check duplicate
    $resDup = $this->getJson(route('contracts.check_code', ['code' => 'HD-UNIQUE-202']));
    $resDup->assertOk()->assertJson(['exists' => true]);

    // Check available
    $resAvail = $this->getJson(route('contracts.check_code', ['code' => 'HD-FREE-888']));
    $resAvail->assertOk()->assertJson(['exists' => false]);

    // Check ignoring self
    $resIgnore = $this->getJson(route('contracts.check_code', ['code' => 'HD-UNIQUE-202', 'ignore_id' => $contract->id]));
    $resIgnore->assertOk()->assertJson(['exists' => false]);
});

test('can show contract in json format', function () {
    $contract = Contract::factory()->create(['code' => 'HD-SHOW-TEST']);

    $response = $this->getJson(route('contracts.show', $contract));

    $response->assertOk();
    $response->assertJsonPath('data.code', 'HD-SHOW-TEST');
});

test('can update an existing contract', function () {
    $contract = Contract::factory()->create([
        'code' => 'HD-UPDATE-OLD',
        'amount' => 10000000,
        'status' => 'Mới',
    ]);

    $response = $this->put(route('contracts.update', $contract), [
        'code' => 'HD-UPDATE-NEW',
        'contract_date' => now()->format('Y-m-d H:i:s'),
        'partner' => 'PARTNER-NEW',
        'amount' => 15000000,
        'status' => 'Đã ký Online',
        'type' => 'Bán hàng',
        'sale_payment_status' => 'Đã thanh toán',
    ]);

    $response->assertRedirect(route('contracts.index'));
    $this->assertDatabaseHas('contracts', [
        'id' => $contract->id,
        'code' => 'HD-UPDATE-NEW',
        'partner' => 'PARTNER-NEW',
        'amount' => 15000000,
        'status' => 'Đã ký Online',
    ]);
});

test('can delete a contract', function () {
    $contract = Contract::factory()->create(['code' => 'HD-TO-DELETE']);

    $response = $this->delete(route('contracts.destroy', $contract));

    $response->assertRedirect(route('contracts.index'));
    $this->assertDatabaseMissing('contracts', ['id' => $contract->id]);
});

test('customer search endpoint returns matching results for autocomplete', function () {
    Customer::factory()->create([
        'code' => 'KH-AUTO-01',
        'name' => 'Công ty TNHH Ánh Sao',
        'tax_id' => '0102030405',
    ]);
    Customer::factory()->create([
        'code' => 'KH-AUTO-02',
        'name' => 'Khách hàng Bình Minh',
        'tax_id' => '0908070605',
    ]);

    $response = $this->getJson(route('customers.search', ['q' => 'Ánh Sao']));

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['code'])->toBe('KH-AUTO-01');
});

test('contract sheetHeaders and toSheetRow match expected 16 columns', function () {
    $headers = Contract::sheetHeaders();

    expect($headers)->toBe([
        'Mã',
        'Số HĐ',
        'Ngày',
        'Đối tác',
        'Số tiền',
        'Trạng thái',
        'Loại',
        'Email',
        'Link',
        'Link báo giá',
        'Ghi chú',
        'Dấu thời gian',
        'Email',
        'NV Sale',
        'Chiết khấu NV sale',
        'Thanh toán cho sale',
    ]);
    expect(count($headers))->toBe(16);

    $contract = Contract::factory()->create([
        'code' => 'HD-ROW-TEST',
        'contract_number' => '10/2026/HDBH',
        'contract_date' => '2026-10-31 11:00:00',
        'partner' => 'KH-002',
        'amount' => 45000000,
        'status' => 'Đã tạo HD online',
        'type' => 'Bán hàng',
        'email' => 'doitac@test.com',
        'link' => 'https://example.com/contract',
        'quote_link' => 'https://example.com/quote',
        'note' => 'Ghi chú HĐ',
        'salesperson' => 'NV-02',
        'sale_discount' => 500000,
        'sale_payment_status' => 'Đã thanh toán',
    ]);

    $row = $contract->toSheetRow();

    expect(count($row))->toBe(16);
    expect($row[0])->toBe('HD-ROW-TEST');
    expect($row[1])->toBe('10/2026/HDBH');
    expect($row[2])->toBe('2026-10-31 11:00:00');
    expect($row[3])->toBe('KH-002');
    expect($row[4])->toBe(45000000.0);
    expect($row[5])->toBe('Đã tạo HD online');
    expect($row[6])->toBe('Bán hàng');
    expect($row[7])->toBe('doitac@test.com');
    expect($row[8])->toBe('https://example.com/contract');
    expect($row[9])->toBe('https://example.com/quote');
    expect($row[10])->toBe('Ghi chú HĐ');
    expect($row[12])->toBe('doitac@test.com');
    expect($row[13])->toBe('NV-02');
    expect($row[14])->toBe(500000.0);
    expect($row[15])->toBe('Đã thanh toán');
});

test('contract fromSheetRow maps row array to attributes correctly by index', function () {
    $row = [
        'HD-IMPORT-001',       // 0: Mã
        '05/2026/HDMB',        // 1: Số HĐ
        '2026-10-30 14:00:00', // 2: Ngày
        'KH-SHEET-02',         // 3: Đối tác
        '25,000,000 ₫',        // 4: Số tiền
        'Đã ký Online',        // 5: Trạng thái
        'Mua hàng',            // 6: Loại
        'info@sheet.vn',       // 7: Email
        'https://sheet.link/hd', // 8: Link
        'https://sheet.link/bg', // 9: Link báo giá
        'Ghi chú HĐ nhập',     // 10: Ghi chú
        '2026-10-31 08:00:00', // 11: Dấu thời gian
        'info@sheet.vn',       // 12: Email
        'Trần Sale',           // 13: NV Sale
        '1,000,000',           // 14: Chiết khấu NV sale
        'Chưa thanh toán',     // 15: Thanh toán cho sale
    ];

    $attr = Contract::fromSheetRow($row);

    expect($attr['code'])->toBe('HD-IMPORT-001');
    expect($attr['contract_number'])->toBe('05/2026/HDMB');
    expect($attr['partner'])->toBe('KH-SHEET-02');
    expect($attr['amount'])->toBe(25000000.0);
    expect($attr['status'])->toBe('Đã ký Online');
    expect($attr['type'])->toBe('Mua hàng');
    expect($attr['email'])->toBe('info@sheet.vn');
    expect($attr['link'])->toBe('https://sheet.link/hd');
    expect($attr['quote_link'])->toBe('https://sheet.link/bg');
    expect($attr['note'])->toBe('Ghi chú HĐ nhập');
    expect($attr['salesperson'])->toBe('Trần Sale');
    expect($attr['sale_discount'])->toBe(1000000.0);
    expect($attr['sale_payment_status'])->toBe('Chưa thanh toán');
});

test('contracts sync fails gracefully if no active sheet config exists', function () {
    $response = $this->post(route('contracts.sync'), ['direction' => 'pull']);

    $response->assertRedirect(route('contracts.index'));
    $response->assertSessionHas('error');
});

test('contract calculateSaleDiscount follows Google Sheet formula with 10k rounding and 0/5000/10000 thresholds', function () {
    // 55.000.000 / 55 - 3 = 999.997 -> floor10k = 990.000, mod = 9997 > 7000 -> +10.000 = 1.000.000
    expect(Contract::calculateSaleDiscount(55000000))->toBe(1000000);

    // calc <= 3000 -> +0
    // 55.165.000 / 55 - 3 = 1.002.997 -> mod 2997 <= 3000 -> 1.000.000
    expect(Contract::calculateSaleDiscount(55165000))->toBe(1000000);

    // calc <= 7000 -> +5000
    // 55.275.000 / 55 - 3 = 1.004.997 -> mod 4997 <= 7000 -> 1.005.000
    expect(Contract::calculateSaleDiscount(55275000))->toBe(1005000);

    // calc > 7000 -> +10000
    // 55.440.000 / 55 - 3 = 1.007.997 -> mod 7997 > 7000 -> 1.010.000
    expect(Contract::calculateSaleDiscount(55440000))->toBe(1010000);

    // User's example: 2390120 calc -> 2390000
    // 131.456.600 / 55 - 3 = 2.390.117 -> mod 117 <= 3000 -> 2.390.000
    expect(Contract::calculateSaleDiscount(131456600))->toBe(2390000);

    // 0 or small amount
    expect(Contract::calculateSaleDiscount(0))->toBe(0);
    expect(Contract::calculateSaleDiscount(100))->toBe(0);
});

test('contract controller sanitizes Vietnamese formatted currency strings with dots', function () {
    $contractData = [
        'code' => 'HD-CURRENCY-01',
        'contract_number' => '01/2026/TEST',
        'contract_date' => '2026-10-31 10:00:00',
        'partner' => 'KH-002',
        'type' => 'Bán hàng',
        'amount' => '45.000.000',
        'status' => 'Mới',
        'salesperson' => 'NV-02',
        'sale_discount' => '900.000',
        'sale_payment_status' => 'Chưa thanh toán',
    ];

    $response = $this->post(route('contracts.store'), $contractData);
    $response->assertRedirect(route('contracts.index'));

    $contract = Contract::where('code', 'HD-CURRENCY-01')->first();
    expect($contract)->not->toBeNull();
    expect((float) $contract->amount)->toBe(45000000.0);
    expect((float) $contract->sale_discount)->toBe(900000.0);
});

test('contract controller auto-calculates sale_discount when not supplied', function () {
    $contractData = [
        'code' => 'HD-AUTO-DISCOUNT',
        'contract_date' => '2026-10-31 10:00:00',
        'partner' => 'KH-002',
        'type' => 'Bán hàng',
        'amount' => '55.000.000',
        'status' => 'Mới',
        'sale_payment_status' => 'Chưa thanh toán',
    ];

    $response = $this->post(route('contracts.store'), $contractData);
    $response->assertRedirect(route('contracts.index'));

    $contract = Contract::where('code', 'HD-AUTO-DISCOUNT')->first();
    expect($contract)->not->toBeNull();
    // 55.000.000 -> 1.000.000
    expect((float) $contract->sale_discount)->toBe(1000000.0);
});

test('contract links are normalized with https prefix when domain without scheme is entered', function () {
    $contractData = [
        'code' => 'HD-URL-NORM',
        'contract_date' => '2026-10-31 10:00:00',
        'partner' => 'KH-002',
        'type' => 'Bán hàng',
        'amount' => 1000000,
        'status' => 'Mới',
        'sale_payment_status' => 'Chưa thanh toán',
        'link' => 'docs.google.com/document/d/xyz',
        'quote_link' => 'drive.google.com/quote/123',
    ];

    $response = $this->post(route('contracts.store'), $contractData);
    $response->assertRedirect(route('contracts.index'));

    $contract = Contract::where('code', 'HD-URL-NORM')->first();
    expect($contract)->not->toBeNull();
    expect($contract->link)->toBe('https://docs.google.com/document/d/xyz');
    expect($contract->quote_link)->toBe('https://drive.google.com/quote/123');
});

test('contract url validation fails on invalid url format', function () {
    $contractData = [
        'code' => 'HD-URL-FAIL',
        'contract_date' => '2026-10-31 10:00:00',
        'partner' => 'KH-002',
        'type' => 'Bán hàng',
        'amount' => 1000000,
        'status' => 'Mới',
        'sale_payment_status' => 'Chưa thanh toán',
        'quote_link' => 'not-a-valid-link',
    ];

    $response = $this->post(route('contracts.store'), $contractData);
    $response->assertSessionHasErrors(['quote_link']);
});
