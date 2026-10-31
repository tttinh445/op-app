<?php

use App\Models\Supplier;

test('suppliers index page can be rendered', function () {
    Supplier::factory()->count(3)->create();

    $response = $this->get(route('suppliers.index'));

    $response->assertStatus(200);
    $response->assertSee('Danh sách Nhà cung cấp');
});

test('suppliers are sorted descending by timestamp by default', function () {
    $s1 = Supplier::factory()->create(['created_at' => now()->subDays(3), 'name' => 'Supplier Old']);
    $s2 = Supplier::factory()->create(['created_at' => now(), 'name' => 'Supplier New']);

    $response = $this->get(route('suppliers.index'));

    $response->assertStatus(200);
    $response->assertSeeInOrder(['Supplier New', 'Supplier Old']);
});

test('can create a new supplier with valid data', function () {
    $supplierData = [
        'code' => 'NCC8888',
        'name' => 'Công ty Phân Phối Thiết Bị Ánh Sáng',
        'type' => 'NPP',
        'zalo' => '0938123456',
        'tax_id' => '0319888777',
        'bank_account' => '999888777',
        'bank_name' => 'Techcombank',
        'address' => '123 Nguyễn Văn Cừ, Quận 5, TP.HCM',
        'phone' => '02838123456',
        'email' => 'sales@anhsang.vn',
        'website' => 'https://anhsang.vn',
        'facebook' => 'https://facebook.com/anhsangvn',
        'note' => 'Ghi chú nhà phân phối',
    ];

    $response = $this->post(route('suppliers.store'), $supplierData);

    $response->assertRedirect(route('suppliers.index'));
    $this->assertDatabaseHas('suppliers', [
        'code' => 'NCC8888',
        'tax_id' => '0319888777',
        'type' => 'NPP',
    ]);
});

test('validation fails when required supplier fields are missing', function () {
    $response = $this->post(route('suppliers.store'), [
        'code' => '',
        'name' => '',
        'type' => '',
        'tax_id' => '',
    ]);

    $response->assertSessionHasErrors(['code', 'name', 'type', 'tax_id']);
});

test('validation fails when supplier code or tax_id are duplicated', function () {
    Supplier::factory()->create([
        'code' => 'NCC_DUP',
        'tax_id' => '5544332211',
    ]);

    $response = $this->post(route('suppliers.store'), [
        'code' => 'NCC_DUP',
        'name' => 'NCC Trùng Mã',
        'type' => 'NCC',
        'tax_id' => '5544332211',
    ]);

    $response->assertSessionHasErrors(['code', 'tax_id']);
});

test('validation fails when supplier email is invalid', function () {
    $response = $this->post(route('suppliers.store'), [
        'code' => 'NCC_BAD_MAIL',
        'name' => 'NCC Bad Email',
        'type' => 'NCC',
        'tax_id' => '6677889900',
        'email' => 'not-a-valid-email',
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('validation fails when supplier type is invalid', function () {
    $response = $this->post(route('suppliers.store'), [
        'code' => 'NCC_BAD_TYPE',
        'name' => 'NCC Bad Type',
        'type' => 'INVALID_TYPE',
        'tax_id' => '7788990011',
    ]);

    $response->assertSessionHasErrors(['type']);
});

test('can update an existing supplier', function () {
    $supplier = Supplier::factory()->create();

    $response = $this->put(route('suppliers.update', $supplier), [
        'code' => $supplier->code,
        'name' => 'NCC Tên Sau Cập Nhật',
        'type' => 'NCC',
        'tax_id' => $supplier->tax_id,
        'email' => 'ncc_updated@gmail.com',
    ]);

    $response->assertRedirect(route('suppliers.index'));
    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => 'NCC Tên Sau Cập Nhật',
    ]);
});

test('can delete a supplier', function () {
    $supplier = Supplier::factory()->create();

    $response = $this->delete(route('suppliers.destroy', $supplier));

    $response->assertRedirect(route('suppliers.index'));
    $this->assertDatabaseMissing('suppliers', [
        'id' => $supplier->id,
    ]);
});

test('check code endpoint returns exists true for duplicate supplier code and false when available', function () {
    $supplier = Supplier::factory()->create(['code' => 'NCC_EXISTS']);

    $res1 = $this->getJson(route('suppliers.check_code', ['code' => 'NCC_EXISTS']));
    $res1->assertOk()
        ->assertJson([
            'exists' => true,
        ]);

    $res2 = $this->getJson(route('suppliers.check_code', ['code' => 'NCC_NEW']));
    $res2->assertOk()
        ->assertJson([
            'exists' => false,
        ]);

    // When ignore_id is provided for same record
    $res3 = $this->getJson(route('suppliers.check_code', ['code' => 'NCC_EXISTS', 'ignore_id' => $supplier->id]));
    $res3->assertOk()
        ->assertJson([
            'exists' => false,
        ]);
});
