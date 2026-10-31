<?php

namespace App\Models;

use App\Contracts\GoogleSheetSyncable;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model implements GoogleSheetSyncable
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'tax_id',
        'phone',
        'email',
        'bank_account',
        'bank_name',
        'note',
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
     * Define the column headers in Google Sheet.
     *
     * @return array<int, string>
     */
    public static function sheetHeaders(): array
    {
        return [
            'Mã',
            'Tên khách hàng',
            'Loại',
            'MST',
            'Điện thoại',
            'Email',
            'Số TK',
            'Ngân hàng',
            'Ghi chú',
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
            $this->name,
            $this->type,
            $this->tax_id,
            $this->phone ?? '',
            $this->email ?? '',
            $this->bank_account ?? '',
            $this->bank_name ?? '',
            $this->note ?? '',
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
            'name' => trim((string) ($row[1] ?? '')),
            'type' => in_array(trim((string) ($row[2] ?? '')), ['Công ty', 'Khách vãng lai'], true)
                ? trim((string) $row[2])
                : 'Khách vãng lai',
            'tax_id' => trim((string) ($row[3] ?? '')),
            'phone' => trim((string) ($row[4] ?? '')) ?: null,
            'email' => trim((string) ($row[5] ?? '')) ?: null,
            'bank_account' => trim((string) ($row[6] ?? '')) ?: null,
            'bank_name' => trim((string) ($row[7] ?? '')) ?: null,
            'note' => trim((string) ($row[8] ?? '')) ?: null,
        ];
    }

    /**
     * Unique identifier for the sheet type.
     */
    public static function sheetType(): string
    {
        return 'customer';
    }
}
