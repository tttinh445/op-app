<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\GoogleSheetConfig;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Google Sheets sample configurations
        $sheetCustomers = GoogleSheetConfig::create([
            'name' => 'Bảng dữ liệu Khách Hàng (Google Sheets Tổng)',
            'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit#gid=0',
            'spreadsheet_id' => '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms',
            'sheet_name' => 'Khách hàng',
            'sheet_type' => 'customer',
            'gid' => '0',
            'range' => 'A1:J',
            'sync_mode' => 'csv_public',
            'is_active' => true,
            'last_synced_at' => now()->subHours(2),
            'notes' => 'File quản lý tập trung danh mục khách hàng công ty và khách lẻ',
        ]);

        $sheetSuppliers = GoogleSheetConfig::create([
            'name' => 'Bảng dữ liệu Nhà Cung Cấp & NPP 2026',
            'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit#gid=142352',
            'spreadsheet_id' => '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms',
            'sheet_name' => 'Nhà cung cấp',
            'sheet_type' => 'supplier',
            'gid' => '142352',
            'range' => 'A1:N',
            'sync_mode' => 'csv_public',
            'is_active' => true,
            'last_synced_at' => now()->subHours(4),
            'notes' => 'Tab sheet Nhà Cung Cấp trên cùng file Google Sheets',
        ]);

        $sheetProducts = GoogleSheetConfig::create([
            'name' => 'Bảng Danh mục Sản Phẩm & Bảng Giá Kho',
            'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/1_xYZSampleSheetIdForProductCatalog9876543210/edit#gid=0',
            'spreadsheet_id' => '1_xYZSampleSheetIdForProductCatalog9876543210',
            'sheet_name' => 'Sản phẩm',
            'sheet_type' => 'product',
            'gid' => '0',
            'range' => 'A1:I',
            'sync_mode' => 'csv_public',
            'is_active' => true,
            'last_synced_at' => now()->subDay(),
            'notes' => 'File Google Sheets riêng biệt quản lý kho hàng',
        ]);

        // 2. Sample Customers
        $sampleCustomers = [
            [
                'code' => 'KH0001',
                'name' => 'Công ty Cổ phần Công Nghệ Minh Quang',
                'type' => 'Công ty',
                'tax_id' => '0315892147',
                'phone' => '0903124567',
                'email' => 'contact@minhquangtech.vn',
                'bank_account' => '1029485729',
                'bank_name' => 'Vietcombank - CN Tân Bình',
                'note' => 'Khách hàng VIP, thanh toán công nợ 30 ngày',
                'google_sheet_config_id' => $sheetCustomers->id,
                'created_at' => now()->subDays(1),
            ],
            [
                'code' => 'KH0002',
                'name' => 'Anh Trần Đình Trọng (Khách lẻ VIP)',
                'type' => 'Khách vãng lai',
                'tax_id' => '8092147581',
                'phone' => '0912445892',
                'email' => 'trong.tran@gmail.com',
                'bank_account' => '0491000123456',
                'bank_name' => 'Techcombank',
                'note' => 'Giao hàng tận nơi, khách quen',
                'google_sheet_config_id' => $sheetCustomers->id,
                'created_at' => now()->subDays(2),
            ],
            [
                'code' => 'KH0003',
                'name' => 'Tập đoàn Xây dựng Delta Quốc Tế',
                'type' => 'Công ty',
                'tax_id' => '0104829104',
                'phone' => '02438891234',
                'email' => 'info@deltacorp.vn',
                'bank_account' => '21510001889234',
                'bank_name' => 'BIDV - CN Cầu Giấy',
                'note' => 'Hợp đồng dự án quý 4',
                'google_sheet_config_id' => $sheetCustomers->id,
                'created_at' => now()->subDays(3),
            ],
            [
                'code' => 'KH0004',
                'name' => 'Chị Lê Thị Mai Phương',
                'type' => 'Khách vãng lai',
                'tax_id' => '8392019482',
                'phone' => '0988771234',
                'email' => 'maiphuong.le@yahoo.com',
                'bank_account' => '888012345678',
                'bank_name' => 'MB Bank',
                'note' => 'Thanh toán tiền mặt hoặc quét mã QR',
                'google_sheet_config_id' => $sheetCustomers->id,
                'created_at' => now()->subDays(5),
            ],
            [
                'code' => 'KH0005',
                'name' => 'Công ty TNHH Thương Mại Dịch Vụ An Phát',
                'type' => 'Công ty',
                'tax_id' => '0317829401',
                'phone' => '02839982341',
                'email' => 'ketoan@anphatlogistics.com',
                'bank_account' => '6868999988',
                'bank_name' => 'ACB - CN Bến Thành',
                'note' => 'Cần xuất hóa đơn điện tử VAT trước ngày 25 hàng tháng',
                'google_sheet_config_id' => $sheetCustomers->id,
                'created_at' => now()->subDays(7),
            ],
        ];

        foreach ($sampleCustomers as $customer) {
            Customer::create($customer);
        }

        // 3. Sample Suppliers
        $sampleSuppliers = [
            [
                'code' => 'NCC001',
                'name' => 'Nhà Phân Phối Thiết Bị Á Châu (ASIANET)',
                'type' => 'NPP',
                'zalo' => '0938112233',
                'tax_id' => '0309988112',
                'bank_account' => '119000182741',
                'bank_name' => 'VietinBank - CN TP.HCM',
                'address' => '128 Đường số 7, KDC Trung Sơn, Bình Chánh, TP.HCM',
                'phone' => '02837582233',
                'email' => 'sales@asianetcorp.vn',
                'website' => 'https://asianetcorp.vn',
                'facebook' => 'https://facebook.com/asianetcorp',
                'note' => 'NPP độc quyền linh kiện chính hãng, chiết khấu 15%',
                'google_sheet_config_id' => $sheetSuppliers->id,
                'created_at' => now()->subDays(1),
            ],
            [
                'code' => 'NCC002',
                'name' => 'Công ty TNHH Sản Xuất Bao Bì Hoàng Nam',
                'type' => 'NCC',
                'zalo' => '0918776655',
                'tax_id' => '0105829144',
                'bank_account' => '0011004382910',
                'bank_name' => 'Vietcombank - Sở giao dịch Hà Nội',
                'address' => 'Lô B4, KCN Thạch Thất, Quốc Oai, Hà Nội',
                'phone' => '02433998877',
                'email' => 'order@hoangnampack.com',
                'website' => 'https://hoangnampack.com',
                'facebook' => 'https://facebook.com/hoangnampack',
                'note' => 'Cung ứng thùng carton và túi đóng gói sản phẩm',
                'google_sheet_config_id' => $sheetSuppliers->id,
                'created_at' => now()->subDays(2),
            ],
            [
                'code' => 'NCC003',
                'name' => 'Tổng Kho Phụ Kiện Quang Trung Global',
                'type' => 'NPP',
                'zalo' => '0908223344',
                'tax_id' => '0314772918',
                'bank_account' => '19033882719014',
                'bank_name' => 'Techcombank - CN Sài Gòn',
                'address' => '456 Quang Trung, Phường 10, Gò Vấp, TP.HCM',
                'phone' => '0908223344',
                'email' => 'phukienquangtrung@gmail.com',
                'website' => 'https://quangtrungstore.vn',
                'facebook' => 'https://facebook.com/quangtrungaccessories',
                'note' => 'Giao hàng nhanh 2h trong nội thành',
                'google_sheet_config_id' => $sheetSuppliers->id,
                'created_at' => now()->subDays(4),
            ],
            [
                'code' => 'NCC004',
                'name' => 'Công ty Xuất Nhập Khẩu VinaTrade',
                'type' => 'NCC',
                'zalo' => '0977889900',
                'tax_id' => '0401829102',
                'bank_account' => '5401000049281',
                'bank_name' => 'BIDV - CN Hải Châu Đà Nẵng',
                'address' => '72 Bạch Đằng, Quận Hải Châu, TP. Đà Nẵng',
                'phone' => '02363889900',
                'email' => 'support@vinatrade.vn',
                'website' => 'https://vinatrade.vn',
                'facebook' => 'https://facebook.com/vinatradevn',
                'note' => 'Nhập khẩu thiết bị trực tiếp từ Nhật Bản và Hàn Quốc',
                'google_sheet_config_id' => $sheetSuppliers->id,
                'created_at' => now()->subDays(6),
            ],
        ];

        foreach ($sampleSuppliers as $supplier) {
            Supplier::create($supplier);
        }

        // 4. Sample Products
        $sampleProducts = [
            [
                'code' => 'SP001',
                'category_code' => 'DM_PHUKIEN',
                'name' => 'Cáp sạc đa năng Siêu Bền Type-C 100W OP-Power',
                'unit' => 'Sợi',
                'supplier_code' => 'NCC001',
                'brand' => 'OP-Power',
                'other_name' => 'Cable Type-C 100W Fast Charge',
                'google_sheet_config_id' => $sheetProducts->id,
            ],
            [
                'code' => 'SP002',
                'category_code' => 'DM_VANPHONG',
                'name' => 'Bộ chuyển đổi USB-C sang HDMI 4K 60Hz Pro',
                'unit' => 'Bộ',
                'supplier_code' => 'NCC003',
                'brand' => 'HyperDrive',
                'other_name' => 'Adapter USB-C to HDMI 4K',
                'google_sheet_config_id' => $sheetProducts->id,
            ],
            [
                'code' => 'SP003',
                'category_code' => 'DM_DIEN',
                'name' => 'Ổ cắm điện thông minh tích hợp sạc nhanh GaN 65W',
                'unit' => 'Cái',
                'supplier_code' => 'NCC001',
                'brand' => 'Panasonic',
                'other_name' => 'Smart Socket GaN 65W',
                'google_sheet_config_id' => $sheetProducts->id,
            ],
            [
                'code' => 'SP004',
                'category_code' => 'DM_PHUKIEN',
                'name' => 'Tai nghe kiểm âm không dây chống ồn chủ động OP-Studio',
                'unit' => 'Chiếc',
                'supplier_code' => 'NCC004',
                'brand' => 'Sony',
                'other_name' => 'Wireless ANC Studio Headphone',
                'google_sheet_config_id' => $sheetProducts->id,
            ],
        ];

        foreach ($sampleProducts as $product) {
            Product::create($product);
        }
    }
}
