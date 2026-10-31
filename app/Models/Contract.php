<?php

namespace App\Models;

use App\Contracts\GoogleSheetSyncable;
use Carbon\Carbon;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

class Contract extends Model implements GoogleSheetSyncable
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    public const TYPES = [
        'Bán hàng',
        'Mua hàng',
    ];

    public const STATUSES = [
        'Mới',
        'Đã tạo HD online',
        'Đã ký Online',
        'Đã ký tay',
    ];

    public const SALE_PAYMENT_STATUSES = [
        'Chưa thanh toán',
        'Đã thanh toán',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'contract_number',
        'contract_date',
        'partner',
        'amount',
        'status',
        'type',
        'email',
        'link',
        'quote_link',
        'note',
        'salesperson',
        'sale_discount',
        'sale_payment_status',
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
            'contract_date' => 'datetime',
            'amount' => 'decimal:2',
            'sale_discount' => 'decimal:2',
            'synced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Optional relationship to Customer as partner by code.
     */
    public function partnerRecord(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'partner', 'code');
    }

    /**
     * Relationship to Google Sheet configuration.
     */
    public function googleSheetConfig(): BelongsTo
    {
        return $this->belongsTo(GoogleSheetConfig::class);
    }

    /**
     * Get display partner label.
     */
    public function getPartnerDisplayNameAttribute(): string
    {
        if ($this->relationLoaded('partnerRecord') && $this->partnerRecord) {
            return "{$this->partnerRecord->name} ({$this->partner})";
        }

        return $this->partner;
    }

    /**
     * Formatted amount string.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) ($this->amount ?? 0), 0, ',', '.').' ₫';
    }

    /**
     * Formatted sale discount string.
     */
    public function getFormattedSaleDiscountAttribute(): string
    {
        if ($this->sale_discount === null) {
            return '—';
        }

        return number_format((float) $this->sale_discount, 0, ',', '.').' ₫';
    }

    /**
     * Calculate sales commission/discount according to Google Sheet formula:
     * LET(
     *   calc; ROUNDDOWN(F9/55 - 3);
     *   FLOOR(calc; 10000) + IFS(
     *     MOD(calc; 10000) <= 3000; 0;
     *     MOD(calc; 10000) <= 7000; 5000;
     *     TRUE; 10000
     *   )
     * )
     */
    public static function calculateSaleDiscount(float|int|string|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        $clean = is_string($amount) ? preg_replace('/[^\d.]/', '', $amount) : $amount;
        $num = (float) $clean;
        if ($num <= 0) {
            return 0;
        }

        $calc = (int) floor(($num / 55) - 3);
        if ($calc <= 0) {
            return 0;
        }

        $floor10k = (int) (floor($calc / 10000) * 10000);
        $mod10k = $calc % 10000;

        if ($mod10k <= 3000) {
            $bonus = 0;
        } elseif ($mod10k <= 7000) {
            $bonus = 5000;
        } else {
            $bonus = 10000;
        }

        return $floor10k + $bonus;
    }

    /**
     * Define the column headers in Google Sheet for Contracts.
     * Cấu trúc: Mã, Số HĐ, Ngày, Đối tác, Số tiền, Trạng thái, Loại, Email, Link, Link báo giá, Ghi chú, Dấu thời gian, Email, NV Sale, Chiết khấu NV sale, Thanh toán cho sale
     *
     * @return array<int, string>
     */
    public static function sheetHeaders(): array
    {
        return [
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
            $this->code,                                                                // 0: Mã (Key column A)
            $this->contract_number ?? '',                                               // 1: Số HĐ
            $this->contract_date?->format('Y-m-d H:i:s') ?? '',                         // 2: Ngày
            $this->partner,                                                             // 3: Đối tác
            (float) ($this->amount ?? 0),                                               // 4: Số tiền
            $this->status ?? 'Mới',                                                     // 5: Trạng thái
            $this->type ?? 'Bán hàng',                                                  // 6: Loại
            $this->email ?? '',                                                         // 7: Email
            $this->link ?? '',                                                          // 8: Link
            $this->quote_link ?? '',                                                    // 9: Link báo giá
            $this->note ?? '',                                                          // 10: Ghi chú
            $this->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'), // 11: Dấu thời gian
            $this->email ?? '',                                                         // 12: Email
            $this->salesperson ?? '',                                                   // 13: NV Sale
            $this->sale_discount !== null ? (float) $this->sale_discount : '',          // 14: Chiết khấu NV sale
            $this->sale_payment_status ?? 'Chưa thanh toán',                            // 15: Thanh toán cho sale
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
        $contractDateStr = trim((string) ($row[2] ?? ''));
        $contractDate = null;
        if ($contractDateStr !== '') {
            try {
                $contractDate = Carbon::parse($contractDateStr);
            } catch (Throwable) {
                $contractDate = now();
            }
        } else {
            $contractDate = now();
        }

        $rawAmount = str_replace([',', ' ', '₫', 'VND', 'vnd'], '', (string) ($row[4] ?? '0'));
        $rawDiscount = str_replace([',', ' ', '₫', 'VND', 'vnd'], '', (string) ($row[14] ?? ''));

        $email = trim((string) ($row[7] ?? ''));
        if ($email === '' && ! empty($row[12])) {
            $email = trim((string) $row[12]);
        }

        return [
            'code' => trim((string) ($row[0] ?? '')),
            'contract_number' => trim((string) ($row[1] ?? '')) ?: null,
            'contract_date' => $contractDate,
            'partner' => trim((string) ($row[3] ?? '')),
            'amount' => is_numeric($rawAmount) ? (float) $rawAmount : 0,
            'status' => in_array(trim((string) ($row[5] ?? '')), self::STATUSES, true) ? trim((string) $row[5]) : 'Mới',
            'type' => in_array(trim((string) ($row[6] ?? '')), self::TYPES, true) ? trim((string) $row[6]) : 'Bán hàng',
            'email' => $email ?: null,
            'link' => trim((string) ($row[8] ?? '')) ?: null,
            'quote_link' => trim((string) ($row[9] ?? '')) ?: null,
            'note' => trim((string) ($row[10] ?? '')) ?: null,
            'salesperson' => trim((string) ($row[13] ?? '')) ?: null,
            'sale_discount' => is_numeric($rawDiscount) ? (float) $rawDiscount : null,
            'sale_payment_status' => in_array(trim((string) ($row[15] ?? '')), self::SALE_PAYMENT_STATUSES, true) ? trim((string) $row[15]) : 'Chưa thanh toán',
        ];
    }

    /**
     * Unique identifier for the sheet type.
     */
    public static function sheetType(): string
    {
        return 'contract';
    }
}
