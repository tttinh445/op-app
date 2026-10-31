<?php

use App\Models\Customer;
use App\Models\GoogleSheetConfig;
use App\Models\PendingSheetSync;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\Http;

test('google sheets config index renders correctly', function () {
    GoogleSheetConfig::factory()->count(2)->create();

    $response = $this->get(route('sheets.index'));

    $response->assertStatus(200);
    $response->assertSee('Quản lý Liên kết Google Sheets');
});

test('can create a google sheet config and auto extract spreadsheet id and gid', function () {
    $url = 'https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit#gid=98765';

    $response = $this->post(route('sheets.store'), [
        'name' => 'File Google Sheet Khách Hàng Test',
        'spreadsheet_url' => $url,
        'sheet_name' => 'Sheet1',
        'sheet_type' => 'customer',
        'sync_mode' => 'csv_public',
    ]);

    $response->assertRedirect(route('sheets.index'));
    $this->assertDatabaseHas('google_sheet_configs', [
        'name' => 'File Google Sheet Khách Hàng Test',
        'spreadsheet_id' => '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms',
        'gid' => '98765',
    ]);
});

test('can export csv for customer model matching headers', function () {
    $response = $this->get(route('sheets.export', ['type' => 'customer']));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $this->assertStringContainsString('Tên khách hàng', $response->getContent());
    $this->assertStringContainsString('Dấu thời gian', $response->getContent());
});

test('adding customer automatically triggers append on linked google sheet', function () {
    Http::fake([
        'https://script.google.com/macros/s/test-app/exec*' => Http::response(['success' => true, 'action' => 'append'], 200),
    ]);

    GoogleSheetConfig::factory()->create([
        'sheet_type' => 'customer',
        'sync_mode' => 'apps_script',
        'apps_script_url' => 'https://script.google.com/macros/s/test-app/exec',
        'is_active' => true,
    ]);

    $response = $this->post(route('customers.store'), [
        'code' => 'KH_SYNC_01',
        'name' => 'Khách Hàng Đồng Bộ Sheet',
        'type' => 'Công ty',
        'tax_id' => '0319888999',
        'email' => 'sync@khachhang.com',
    ]);

    $response->assertRedirect(route('customers.index'));

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return $body['action'] === 'append'
            && $body['key'] === 'KH_SYNC_01'
            && $body['row'][0] === 'KH_SYNC_01';
    });
});

test('updating customer automatically triggers update on linked google sheet', function () {
    Http::fake([
        'https://script.google.com/macros/s/test-app/exec*' => Http::response(['success' => true, 'action' => 'update'], 200),
    ]);

    $config = GoogleSheetConfig::factory()->create([
        'sheet_type' => 'customer',
        'sync_mode' => 'apps_script',
        'apps_script_url' => 'https://script.google.com/macros/s/test-app/exec',
        'is_active' => true,
    ]);

    $customer = Customer::factory()->create([
        'code' => 'KH_OLD',
        'google_sheet_config_id' => $config->id,
    ]);

    $response = $this->put(route('customers.update', $customer), [
        'code' => 'KH_NEW',
        'name' => 'Tên Mới',
        'type' => 'Công ty',
        'tax_id' => $customer->tax_id,
    ]);

    $response->assertRedirect(route('customers.index'));

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return $body['action'] === 'update'
            && $body['key'] === 'KH_NEW'
            && $body['originalKey'] === 'KH_OLD';
    });
});

test('deleting customer automatically triggers delete on linked google sheet', function () {
    Http::fake([
        'https://script.google.com/macros/s/test-app/exec*' => Http::response(['success' => true, 'action' => 'delete'], 200),
    ]);

    $config = GoogleSheetConfig::factory()->create([
        'sheet_type' => 'customer',
        'sync_mode' => 'apps_script',
        'apps_script_url' => 'https://script.google.com/macros/s/test-app/exec',
        'is_active' => true,
    ]);

    $customer = Customer::factory()->create([
        'code' => 'KH_TO_DELETE',
        'google_sheet_config_id' => $config->id,
    ]);

    $response = $this->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'));

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return $body['action'] === 'delete'
            && $body['key'] === 'KH_TO_DELETE';
    });
});

test('adding, updating and deleting supplier triggers google sheet sync', function () {
    Http::fake([
        'https://script.google.com/macros/s/test-supplier/exec*' => Http::response(['success' => true], 200),
    ]);

    $config = GoogleSheetConfig::factory()->create([
        'sheet_type' => 'supplier',
        'sync_mode' => 'apps_script',
        'apps_script_url' => 'https://script.google.com/macros/s/test-supplier/exec',
        'is_active' => true,
    ]);

    // 1. Create
    $this->post(route('suppliers.store'), [
        'code' => 'NCC_SYNC_01',
        'name' => 'Nhà Cung Cấp Sync',
        'type' => 'NCC',
        'tax_id' => '0318887771',
    ]);

    Http::assertSent(fn ($req) => json_decode($req->body(), true)['action'] === 'append');

    // 2. Update
    $supplier = Supplier::where('code', 'NCC_SYNC_01')->firstOrFail();
    $this->put(route('suppliers.update', $supplier), [
        'code' => 'NCC_SYNC_01',
        'name' => 'Nhà Cung Cấp Đã Cập Nhật',
        'type' => 'NPP',
        'tax_id' => '0318887771',
    ]);

    Http::assertSent(fn ($req) => json_decode($req->body(), true)['action'] === 'update');

    // 3. Delete
    $this->delete(route('suppliers.destroy', $supplier));
    Http::assertSent(fn ($req) => json_decode($req->body(), true)['action'] === 'delete');
});

test('adding, updating and deleting product triggers google sheet sync', function () {
    Http::fake([
        'https://script.google.com/macros/s/test-product/exec*' => Http::response(['success' => true], 200),
    ]);

    $config = GoogleSheetConfig::factory()->create([
        'sheet_type' => 'product',
        'sync_mode' => 'apps_script',
        'apps_script_url' => 'https://script.google.com/macros/s/test-product/exec',
        'is_active' => true,
    ]);

    // 1. Create
    $this->post(route('products.store'), [
        'code' => 'SP_SYNC_01',
        'name' => 'Sản Phẩm Sync',
        'unit' => 'Cái',
    ]);

    Http::assertSent(fn ($req) => json_decode($req->body(), true)['action'] === 'append');

    // 2. Update
    $product = Product::where('code', 'SP_SYNC_01')->firstOrFail();
    $this->put(route('products.update', $product), [
        'code' => 'SP_SYNC_01',
        'name' => 'Sản Phẩm Tên Mới',
        'unit' => 'Hộp',
    ]);

    Http::assertSent(fn ($req) => json_decode($req->body(), true)['action'] === 'update');

    // 3. Delete
    $this->delete(route('products.destroy', $product));
    Http::assertSent(fn ($req) => json_decode($req->body(), true)['action'] === 'delete');
});

test('push all records sends sync_all payload to google sheet', function () {
    Http::fake([
        'https://script.google.com/macros/s/test-push/exec*' => Http::response(['success' => true], 200),
    ]);

    $config = GoogleSheetConfig::factory()->create([
        'sheet_type' => 'customer',
        'sync_mode' => 'apps_script',
        'apps_script_url' => 'https://script.google.com/macros/s/test-push/exec',
        'is_active' => true,
    ]);

    Customer::factory()->count(3)->create(['google_sheet_config_id' => $config->id]);

    $response = $this->post(route('customers.sync', ['config_id' => $config->id, 'direction' => 'push_all']));

    $response->assertRedirect(route('customers.index'));

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return $body['action'] === 'sync_all'
            && count($body['rows']) === 3
            && ! empty($body['headers']);
    });
});

test('push all records falls back to append when legacy apps script returns invalid action error', function () {
    Http::fake([
        'https://script.google.com/macros/s/test-legacy/exec*' => Http::sequence()
            ->push(['success' => false, 'message' => 'Hành động không hợp lệ'], 200) // Initial sync_all returns invalid action
            ->push(['success' => true, 'message' => 'Đã thêm dòng mới'], 200) // Fallback append row 1
            ->push(['success' => true, 'message' => 'Đã thêm dòng mới'], 200), // Fallback append row 2
    ]);

    $config = GoogleSheetConfig::factory()->create([
        'sheet_type' => 'customer',
        'sync_mode' => 'apps_script',
        'apps_script_url' => 'https://script.google.com/macros/s/test-legacy/exec',
        'is_active' => true,
    ]);

    Customer::factory()->count(2)->create(['google_sheet_config_id' => $config->id]);

    $response = $this->post(route('customers.sync', ['config_id' => $config->id, 'direction' => 'push_all']));

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');
});

test('push marked changes only sends changes with sync_changes payload and marks records as synced', function () {
    Http::fake([
        'https://script.google.com/macros/s/test-marked/exec*' => Http::response([
            'success' => true,
            'created' => 1,
            'updated' => 1,
            'deleted' => 1,
        ], 200),
    ]);

    $config = GoogleSheetConfig::factory()->create([
        'sheet_type' => 'customer',
        'sync_mode' => 'apps_script',
        'apps_script_url' => 'https://script.google.com/macros/s/test-marked/exec',
        'is_active' => true,
    ]);

    // Already synced record - should NOT be pushed
    $syncedCustomer = Customer::factory()->create([
        'code' => 'KH_SYNCED',
        'sync_status' => 'synced',
        'google_sheet_config_id' => $config->id,
    ]);

    // Pending create record - should be pushed
    $newCustomer = Customer::factory()->create([
        'code' => 'KH_NEW',
        'sync_status' => 'pending_create',
        'google_sheet_config_id' => $config->id,
    ]);

    // Pending update record - should be pushed
    $updatedCustomer = Customer::factory()->create([
        'code' => 'KH_UPDATED',
        'sync_status' => 'pending_update',
        'google_sheet_config_id' => $config->id,
    ]);

    // Pending delete record - should be pushed
    PendingSheetSync::create([
        'sheet_type' => 'customer',
        'action' => 'delete',
        'code' => 'KH_DELETED',
        'status' => 'pending',
    ]);

    $response = $this->post(route('customers.sync', ['config_id' => $config->id, 'direction' => 'push']));

    $response->assertRedirect(route('customers.index'));

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return $body['action'] === 'sync_changes'
            && count($body['creates']) === 1
            && $body['creates'][0][0] === 'KH_NEW'
            && count($body['updates']) === 1
            && $body['updates'][0]['key'] === 'KH_UPDATED'
            && count($body['deletes']) === 1
            && $body['deletes'][0] === 'KH_DELETED';
    });

    // Check DB statuses transitioned to synced
    expect($newCustomer->fresh()->sync_status)->toBe('synced')
        ->and($updatedCustomer->fresh()->sync_status)->toBe('synced')
        ->and(PendingSheetSync::where('code', 'KH_DELETED')->first()->status)->toBe('synced');
});

test('push marked changes falls back gracefully if legacy apps script returns invalid action', function () {
    Http::fake([
        'https://script.google.com/macros/s/test-fallback/exec*' => Http::sequence()
            ->push(['success' => false, 'message' => 'Hành động không hợp lệ'], 200) // sync_changes rejected
            ->push(['success' => true, 'action' => 'delete'], 200) // Fallback delete
            ->push(['success' => true, 'action' => 'append'], 200), // Fallback append
    ]);

    $config = GoogleSheetConfig::factory()->create([
        'sheet_type' => 'customer',
        'sync_mode' => 'apps_script',
        'apps_script_url' => 'https://script.google.com/macros/s/test-fallback/exec',
        'is_active' => true,
    ]);

    Customer::factory()->create([
        'code' => 'KH_PENDING_CREATE',
        'sync_status' => 'pending_create',
    ]);

    PendingSheetSync::create([
        'sheet_type' => 'customer',
        'action' => 'delete',
        'code' => 'KH_PENDING_DELETE',
        'status' => 'pending',
    ]);

    $response = $this->post(route('customers.sync', ['config_id' => $config->id, 'direction' => 'push']));

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');
});

test('push marked changes returns error when legacy apps script rejects deletion', function () {
    Http::fake([
        'https://script.google.com/macros/s/test-error/exec*' => Http::sequence()
            ->push(['success' => false, 'message' => 'Hành động không hợp lệ'], 200) // sync_changes rejected
            ->push(['success' => false, 'message' => 'Hành động không hợp lệ'], 200), // delete rejected
    ]);

    $config = GoogleSheetConfig::factory()->create([
        'sheet_type' => 'customer',
        'sync_mode' => 'apps_script',
        'apps_script_url' => 'https://script.google.com/macros/s/test-error/exec',
        'is_active' => true,
    ]);

    PendingSheetSync::create([
        'sheet_type' => 'customer',
        'action' => 'delete',
        'code' => 'KH_PENDING_DELETE',
        'status' => 'pending',
    ]);

    $response = $this->post(route('customers.sync', ['config_id' => $config->id, 'direction' => 'push']));

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('error');
    expect(PendingSheetSync::where('code', 'KH_PENDING_DELETE')->first()->status)->toBe('pending');
});

test('google sheet config resolves key column index accurately', function () {
    $cfgA = new GoogleSheetConfig(['primary_key_column' => 'A']);
    expect($cfgA->getKeyColumnIndex())->toBe(0)
        ->and($cfgA->getKeyColumnLetter())->toBe('A');

    $cfgB = new GoogleSheetConfig(['primary_key_column' => 'b']);
    expect($cfgB->getKeyColumnIndex())->toBe(1)
        ->and($cfgB->getKeyColumnLetter())->toBe('B');

    $cfgC = new GoogleSheetConfig(['primary_key_column' => 'C']);
    expect($cfgC->getKeyColumnIndex())->toBe(2)
        ->and($cfgC->getKeyColumnLetter())->toBe('C');

    $cfgEmpty = new GoogleSheetConfig(['primary_key_column' => null]);
    expect($cfgEmpty->getKeyColumnIndex())->toBe(0)
        ->and($cfgEmpty->getKeyColumnLetter())->toBe('A');

    $cfgAA = new GoogleSheetConfig(['primary_key_column' => 'AA']);
    expect($cfgAA->getKeyColumnIndex())->toBe(26)
        ->and($cfgAA->getKeyColumnLetter())->toBe('AA');
});

test('can create and update google sheet config with custom primary key column', function () {
    $url = 'https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit';

    // 1. Create with custom column 'B'
    $resCreate = $this->post(route('sheets.store'), [
        'name' => 'Sheet với khoá chính B',
        'spreadsheet_url' => $url,
        'sheet_name' => 'Sheet1',
        'sheet_type' => 'customer',
        'sync_mode' => 'csv_public',
        'primary_key_column' => 'b',
    ]);
    $resCreate->assertRedirect(route('sheets.index'));

    $config = GoogleSheetConfig::where('name', 'Sheet với khoá chính B')->firstOrFail();
    expect($config->primary_key_column)->toBe('B')
        ->and($config->getKeyColumnIndex())->toBe(1);

    // 2. Update to column 'C'
    $resUpdate = $this->put(route('sheets.update', $config), [
        'name' => 'Sheet với khoá chính C',
        'spreadsheet_url' => $url,
        'sheet_name' => 'Sheet1',
        'sheet_type' => 'customer',
        'sync_mode' => 'csv_public',
        'primary_key_column' => 'C',
    ]);
    $resUpdate->assertRedirect(route('sheets.index'));
    expect($config->fresh()->primary_key_column)->toBe('C')
        ->and($config->fresh()->getKeyColumnIndex())->toBe(2);
});

test('pushing marked changes sends configured keyColumn and keyColumnIndex to google sheet', function () {
    Http::fake([
        'https://script.google.com/macros/s/test-custom-key/exec*' => Http::response([
            'success' => true,
            'created' => 1,
            'updated' => 0,
            'deleted' => 0,
        ], 200),
    ]);

    $config = GoogleSheetConfig::factory()->create([
        'sheet_type' => 'customer',
        'sync_mode' => 'apps_script',
        'apps_script_url' => 'https://script.google.com/macros/s/test-custom-key/exec',
        'primary_key_column' => 'B',
        'is_active' => true,
    ]);

    Customer::factory()->create([
        'code' => 'KH_KEY_B',
        'sync_status' => 'pending_create',
        'google_sheet_config_id' => $config->id,
    ]);

    $this->post(route('customers.sync', ['config_id' => $config->id, 'direction' => 'push']));

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return $body['action'] === 'sync_changes'
            && ($body['keyColumn'] ?? null) === 'B'
            && ($body['keyColumnIndex'] ?? null) === 1;
    });
});
