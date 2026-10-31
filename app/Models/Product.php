<?php

namespace App\Models;

use App\Contracts\GoogleSheetSyncable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model implements GoogleSheetSyncable
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'category_code',
        'name',
        'unit',
        'supplier_code',
        'brand',
        'other_name',
        'google_sheet_config_id',
        'synced_at',
        'sync_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relationship to Google Sheet configuration.
     */
    public function googleSheetConfig(): BelongsTo
    {
        return $this->belongsTo(GoogleSheetConfig::class);
    }

    /**
     * Relationship to Supplier (NPP/NCC).
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_code', 'code');
    }

    /**
     * Define the column headers in Google Sheet.
     *
     * @return array<int, string>
     */
    public static function sheetHeaders(): array
    {
        return [
            'Mã',
            'Mã danh mục',
            'Tên sản phẩm',
            'ĐVT',
            'NCC/NPP',
            'Hãng sản xuất',
            'Tên khác',
            'Dấu thời gian',
        ];
    }

    /**
     * Map model attributes to Google Sheet row values.
     *
     * @return array<int, mixed>
     */
    public function toSheetRow(): array
    {
        return [
            $this->code,
            $this->category_code ?? '',
            $this->name,
            $this->unit ?? 'Cái',
            $this->supplier_code ?? '',
            $this->brand ?? '',
            $this->other_name ?? '',
            $this->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Map raw row data from Google Sheet into model attributes.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public static function fromSheetRow(array $row): array
    {
        return [
            'code' => trim((string) ($row[0] ?? '')),
            'category_code' => trim((string) ($row[1] ?? '')) ?: null,
            'name' => trim((string) ($row[2] ?? '')),
            'unit' => trim((string) ($row[3] ?? '')) ?: 'Cái',
            'supplier_code' => trim((string) ($row[4] ?? '')) ?: null,
            'brand' => trim((string) ($row[5] ?? '')) ?: null,
            'other_name' => trim((string) ($row[6] ?? '')) ?: null,
        ];
    }

    /**
     * Unique identifier for the sheet type.
     */
    public static function sheetType(): string
    {
        return 'product';
    }
}
