<?php

use App\Models\Product;
use App\Models\Supplier;

test('products index page can be rendered with all required columns', function () {
    $supplier = Supplier::factory()->create([
        'code' => 'NCC_TEST_01',
        'name' => 'Nhà Phân Phối Test',
        'type' => 'NPP',
    ]);

    $product = Product::factory()->create([
        'code' => 'SP_COL_01',
        'category_code' => 'DM_TEST',
        'name' => 'Sản phẩm Kiểm thử Cột',
        'unit' => 'Cuộn',
        'supplier_code' => $supplier->code,
        'brand' => 'Sony',
        'other_name' => 'Model Pro V2',
    ]);

    $response = $this->get(route('products.index'));

    $response->assertStatus(200);
    $response->assertSee('Danh sách Sản phẩm');
    $response->assertSee('SP_COL_01');
    $response->assertSee('DM_TEST');
    $response->assertSee('Sản phẩm Kiểm thử Cột');
    $response->assertSee('Cuộn');
    $response->assertSee('NCC_TEST_01');
    $response->assertSee('Nhà Phân Phối Test');
    $response->assertSee('Sony');
    $response->assertSee('Model Pro V2');
});

test('supplier search endpoint returns server-side results for autocomplete', function () {
    Supplier::factory()->create([
        'code' => 'NCC_AUTO_1',
        'name' => 'Công ty Thiết Bị Hoàng Long',
        'type' => 'NPP',
        'tax_id' => '0319998881',
    ]);

    Supplier::factory()->create([
        'code' => 'NCC_AUTO_2',
        'name' => 'Đại Lý Văn Phòng Phẩm An Nam',
        'type' => 'NCC',
        'tax_id' => '0319998882',
    ]);

    // Search by name
    $response = $this->getJson(route('suppliers.search', ['q' => 'Hoàng Long']));
    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.code', 'NCC_AUTO_1');
    $response->assertJsonPath('data.0.name', 'Công ty Thiết Bị Hoàng Long');

    // Search by code
    $response2 = $this->getJson(route('suppliers.search', ['q' => 'AUTO_2']));
    $response2->assertStatus(200);
    $response2->assertJsonCount(1, 'data');
    $response2->assertJsonPath('data.0.code', 'NCC_AUTO_2');
});

test('can create a new product with required and custom columns', function () {
    $supplier = Supplier::factory()->create([
        'code' => 'NCC_SUP_99',
        'name' => 'Nhà Cung Cấp 99',
    ]);

    $response = $this->post(route('products.store'), [
        'code' => 'SP_NEW_01',
        'category_code' => 'DM_DIEN',
        'name' => 'Bộ bàn phím cơ OP Master',
        'unit' => 'Bộ',
        'supplier_code' => $supplier->code,
        'brand' => 'OP-Brand',
        'other_name' => 'OP Keyboard 2026',
    ]);

    $response->assertRedirect(route('products.index'));
    $this->assertDatabaseHas('products', [
        'code' => 'SP_NEW_01',
        'category_code' => 'DM_DIEN',
        'name' => 'Bộ bàn phím cơ OP Master',
        'unit' => 'Bộ',
        'supplier_code' => 'NCC_SUP_99',
        'brand' => 'OP-Brand',
        'other_name' => 'OP Keyboard 2026',
    ]);
});

test('code is required and must be unique', function () {
    Product::factory()->create(['code' => 'SP_DUPLICATE']);

    // 1. Missing code
    $resMissing = $this->post(route('products.store'), [
        'name' => 'Sản phẩm test',
        'unit' => 'Cái',
    ]);
    $resMissing->assertSessionHasErrors('code');

    // 2. Duplicate code
    $resDuplicate = $this->post(route('products.store'), [
        'code' => 'SP_DUPLICATE',
        'name' => 'Sản phẩm trùng mã',
        'unit' => 'Cái',
    ]);
    $resDuplicate->assertSessionHasErrors('code');
});

test('can update an existing product with new columns', function () {
    $product = Product::factory()->create([
        'code' => 'SP_EDIT_01',
        'name' => 'Tên Ban Đầu',
        'unit' => 'Cái',
    ]);

    $response = $this->put(route('products.update', $product), [
        'code' => 'SP_EDIT_01',
        'category_code' => 'DM_UPDATE',
        'name' => 'Tên SP Mới',
        'unit' => 'Thùng',
        'supplier_code' => 'NCC002',
        'brand' => 'Panasonic',
        'other_name' => 'Tên Khác Đã Đổi',
    ]);

    $response->assertRedirect(route('products.index'));
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Tên SP Mới',
        'category_code' => 'DM_UPDATE',
        'unit' => 'Thùng',
        'supplier_code' => 'NCC002',
        'brand' => 'Panasonic',
        'other_name' => 'Tên Khác Đã Đổi',
    ]);
});

test('can delete a product', function () {
    $product = Product::factory()->create();

    $response = $this->delete(route('products.destroy', $product));

    $response->assertRedirect(route('products.index'));
    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});

test('can filter and search products by supplier', function () {
    $supA = Supplier::factory()->create(['code' => 'NCC_A', 'name' => 'Công ty Cổ phần Alpha']);
    $supB = Supplier::factory()->create(['code' => 'NCC_B', 'name' => 'Công ty TNHH Beta']);

    Product::factory()->create([
        'code' => 'SP_ALPHA',
        'name' => 'Sản phẩm Alpha',
        'supplier_code' => $supA->code,
    ]);

    Product::factory()->create([
        'code' => 'SP_BETA',
        'name' => 'Sản phẩm Beta',
        'supplier_code' => $supB->code,
    ]);

    // 1. Filter by supplier_code dropdown
    $resFilter = $this->get(route('products.index', ['supplier_code' => 'NCC_A']));
    $resFilter->assertStatus(200);
    $resFilter->assertSee('SP_ALPHA');
    $resFilter->assertDontSee('SP_BETA');

    // 2. Search by supplier name
    $resSearch = $this->get(route('products.index', ['search' => 'Beta']));
    $resSearch->assertStatus(200);
    $resSearch->assertSee('SP_BETA');
    $resSearch->assertDontSee('SP_ALPHA');
});

test('check code endpoint returns exists true for duplicate product code and false when available', function () {
    $product = Product::factory()->create(['code' => 'SP_EXISTS']);

    $res1 = $this->getJson(route('products.check_code', ['code' => 'SP_EXISTS']));
    $res1->assertOk()
        ->assertJson([
            'exists' => true,
        ]);

    $res2 = $this->getJson(route('products.check_code', ['code' => 'SP_NEW']));
    $res2->assertOk()
        ->assertJson([
            'exists' => false,
        ]);

    // When ignore_id is provided for same record
    $res3 = $this->getJson(route('products.check_code', ['code' => 'SP_EXISTS', 'ignore_id' => $product->id]));
    $res3->assertOk()
        ->assertJson([
            'exists' => false,
        ]);
});
