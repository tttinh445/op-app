<?php

use App\Models\Employee;

test('settings root redirects to employees settings', function () {
    $response = $this->get(route('settings.index'));

    $response->assertRedirect(route('settings.employees.index'));
});

test('employees index page can be rendered', function () {
    Employee::factory()->count(3)->create();

    $response = $this->get(route('settings.employees.index'));

    $response->assertStatus(200);
    $response->assertSee('Nhân viên');
    $response->assertSee('Mã NV');
    $response->assertSee('Tên NV');
    $response->assertSee('Ngày tạo');
});

test('employees are sorted descending by created_at by default', function () {
    $e1 = Employee::factory()->create(['created_at' => now()->subDays(2), 'name' => 'Nhân viên Cũ']);
    $e2 = Employee::factory()->create(['created_at' => now(), 'name' => 'Nhân viên Mới']);

    $response = $this->get(route('settings.employees.index'));

    $response->assertStatus(200);
    $response->assertSeeInOrder(['Nhân viên Mới', 'Nhân viên Cũ']);
});

test('default employees are created via migration', function () {
    $this->assertDatabaseHas('employees', ['code' => 'NV-1', 'name' => 'Quy']);
    $this->assertDatabaseHas('employees', ['code' => 'NV-2', 'name' => 'Dung']);
    $this->assertDatabaseHas('employees', ['code' => 'NV-3', 'name' => 'Binh']);
});

test('employees are paginated at 30 items per page', function () {
    Employee::factory()->count(35)->create();

    $response = $this->get(route('settings.employees.index'));

    $response->assertStatus(200);
    $this->assertCount(30, $response->viewData('employees'));
    expect($response->viewData('employees')->total())->toBe(Employee::count());
});

test('can create a new employee with valid data', function () {
    $employeeData = [
        'code' => 'NV_TEST_01',
        'name' => 'Nguyễn Văn Test',
    ];

    $response = $this->post(route('settings.employees.store'), $employeeData);

    $response->assertRedirect(route('settings.employees.index'));
    $this->assertDatabaseHas('employees', [
        'code' => 'NV_TEST_01',
        'name' => 'Nguyễn Văn Test',
    ]);
});

test('validation fails when required fields for employee are missing', function () {
    $response = $this->post(route('settings.employees.store'), [
        'code' => '',
        'name' => '',
    ]);

    $response->assertSessionHasErrors(['code', 'name']);
});

test('validation fails when employee code is duplicate', function () {
    $existing = Employee::factory()->create(['code' => 'NV_DUPLICATE']);

    $response = $this->post(route('settings.employees.store'), [
        'code' => 'NV_DUPLICATE',
        'name' => 'Trùng Mã',
    ]);

    $response->assertSessionHasErrors(['code']);
});

test('can update an existing employee', function () {
    $employee = Employee::factory()->create(['code' => 'NV_ORIGINAL', 'name' => 'Tên Cũ']);

    $response = $this->put(route('settings.employees.update', $employee), [
        'code' => 'NV_UPDATED',
        'name' => 'Tên Mới',
    ]);

    $response->assertRedirect(route('settings.employees.index'));
    $this->assertDatabaseHas('employees', [
        'id' => $employee->id,
        'code' => 'NV_UPDATED',
        'name' => 'Tên Mới',
    ]);
});

test('updating employee can keep existing code without duplicate error', function () {
    $employee = Employee::factory()->create(['code' => 'NV_SAME']);

    $response = $this->put(route('settings.employees.update', $employee), [
        'code' => 'NV_SAME',
        'name' => 'Tên Mới Cùng Mã',
    ]);

    $response->assertRedirect(route('settings.employees.index'));
    $this->assertDatabaseHas('employees', [
        'id' => $employee->id,
        'name' => 'Tên Mới Cùng Mã',
    ]);
});

test('can delete an employee', function () {
    $employee = Employee::factory()->create();

    $response = $this->delete(route('settings.employees.destroy', $employee));

    $response->assertRedirect(route('settings.employees.index'));
    $this->assertDatabaseMissing('employees', [
        'id' => $employee->id,
    ]);
});

test('check code endpoint returns exists true for duplicate code and false when available', function () {
    $employee = Employee::factory()->create(['code' => 'NV_EXISTS']);

    $res1 = $this->getJson(route('settings.employees.check_code', ['code' => 'NV_EXISTS']));
    $res1->assertOk()
        ->assertJson([
            'exists' => true,
        ]);

    $res2 = $this->getJson(route('settings.employees.check_code', ['code' => 'NV_AVAILABLE']));
    $res2->assertOk()
        ->assertJson([
            'exists' => false,
        ]);

    // When ignore_id is provided for same record
    $res3 = $this->getJson(route('settings.employees.check_code', [
        'code' => 'NV_EXISTS',
        'ignore_id' => $employee->id,
    ]));
    $res3->assertOk()
        ->assertJson([
            'exists' => false,
        ]);
});
