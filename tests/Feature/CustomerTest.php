<?php

use App\Models\Customer;

test('customers index page can be rendered', function () {
    Customer::factory()->count(3)->create();

    $response = $this->get(route('customers.index'));

    $response->assertStatus(200);
    $response->assertSee('Danh sách Khách hàng');
});

test('customers are sorted descending by timestamp by default', function () {
    $c1 = Customer::factory()->create(['created_at' => now()->subDays(2), 'name' => 'Old Customer']);
    $c2 = Customer::factory()->create(['created_at' => now(), 'name' => 'New Customer']);

    $response = $this->get(route('customers.index'));

    $response->assertStatus(200);
    $response->assertSeeInOrder(['New Customer', 'Old Customer']);
});

test('can create a new customer with valid data', function () {
    $customerData = [
        'code' => 'KH9999',
        'name' => 'Công ty TNHH Thử Nghiệm',
        'type' => 'Công ty',
        'tax_id' => '0319988776',
        'phone' => '0909123456',
        'email' => 'test@thunghiem.com',
        'bank_account' => '123456789',
        'bank_name' => 'Vietcombank',
        'note' => 'Ghi chú thử nghiệm',
    ];

    $response = $this->post(route('customers.store'), $customerData);

    $response->assertRedirect(route('customers.index'));
    $this->assertDatabaseHas('customers', [
        'code' => 'KH9999',
        'tax_id' => '0319988776',
    ]);
});

test('validation fails when required fields are missing', function () {
    $response = $this->post(route('customers.store'), [
        'code' => '',
        'name' => '',
        'type' => '',
        'tax_id' => '',
    ]);

    $response->assertSessionHasErrors(['code', 'name', 'type', 'tax_id']);
});

test('validation fails when code or tax_id are duplicated', function () {
    $existing = Customer::factory()->create([
        'code' => 'KH_DUP',
        'tax_id' => '1122334455',
    ]);

    $response = $this->post(route('customers.store'), [
        'code' => 'KH_DUP',
        'name' => 'Công ty Khác',
        'type' => 'Công ty',
        'tax_id' => '1122334455',
    ]);

    $response->assertSessionHasErrors(['code', 'tax_id']);
});

test('validation fails when email has invalid format', function () {
    $response = $this->post(route('customers.store'), [
        'code' => 'KH_EMAIL',
        'name' => 'Test Email Customer',
        'type' => 'Khách vãng lai',
        'tax_id' => '9988776655',
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('can update an existing customer', function () {
    $customer = Customer::factory()->create();

    $response = $this->put(route('customers.update', $customer), [
        'code' => $customer->code,
        'name' => 'Tên Mới Sau Khi Cập Nhật',
        'type' => 'Công ty',
        'tax_id' => $customer->tax_id,
        'email' => 'updated@domain.com',
    ]);

    $response->assertRedirect(route('customers.index'));
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Tên Mới Sau Khi Cập Nhật',
    ]);
});

test('can delete a customer', function () {
    $customer = Customer::factory()->create();

    $response = $this->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'));
    $this->assertDatabaseMissing('customers', [
        'id' => $customer->id,
    ]);
});

test('check code endpoint returns exists true for duplicate code and false when available', function () {
    $customer = Customer::factory()->create(['code' => 'KH_EXISTS']);

    $res1 = $this->getJson(route('customers.check_code', ['code' => 'KH_EXISTS']));
    $res1->assertOk()
        ->assertJson([
            'exists' => true,
        ]);

    $res2 = $this->getJson(route('customers.check_code', ['code' => 'KH_NEW']));
    $res2->assertOk()
        ->assertJson([
            'exists' => false,
        ]);

    // When ignore_id is provided for same record
    $res3 = $this->getJson(route('customers.check_code', ['code' => 'KH_EXISTS', 'ignore_id' => $customer->id]));
    $res3->assertOk()
        ->assertJson([
            'exists' => false,
        ]);
});
