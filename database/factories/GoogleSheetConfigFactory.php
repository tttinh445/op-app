<?php

namespace Database\Factories;

use App\Models\GoogleSheetConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoogleSheetConfig>
 */
class GoogleSheetConfigFactory extends Factory
{
    protected $model = GoogleSheetConfig::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $id = fake()->regexify('[a-zA-Z0-9_-]{44}');

        return [
            'name' => 'File Google Sheets: '.fake()->words(2, true),
            'spreadsheet_url' => "https://docs.google.com/spreadsheets/d/{$id}/edit",
            'spreadsheet_id' => $id,
            'sheet_name' => 'Khách hàng',
            'sheet_type' => 'customer',
            'gid' => '0',
            'range' => 'A1:Z',
            'primary_key_column' => 'A',
            'sync_mode' => 'csv_public',
            'is_active' => true,
            'last_synced_at' => now(),
            'notes' => 'Tự động tạo mẫu kết nối',
        ];
    }
}
