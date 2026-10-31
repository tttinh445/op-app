<?php

namespace App\Models;

use App\Contracts\GoogleSheetSyncable;
use Carbon\Carbon;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

class Order extends Model implements GoogleSheetSyncable
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const TYPES = [
        'Đơn hàng',
        'Dự án',
    ];

    public const STATUSES = [
        'Mới',
        'Đang xử lý',
        'Đang giao hàng',
        'Hoàn thành',
        'Đã giao hàng',
        'Đang thi công',
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
        'order_date',
        'code',
        'name',
        'customer',
        'type',
        'total_amount',
        'status',
        'quote_url',
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
            'order_date' => 'datetime',
            'total_amount' => 'decimal:2',
            'sale_discount' => 'decimal:2',
            'synced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Optional relationship to Customer by code.
     */
    public function customerRecord(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer', 'code');
    }

    /**
     * Relationship to Google Sheet configuration.
     */
    public function googleSheetConfig(): BelongsTo
    {
        return $this->belongsTo(GoogleSheetConfig::class);
    }

    /**
     * Get display customer label.
     */
    public function getCustomerDisplayNameAttribute(): string
    {
        if ($this->relationLoaded('customerRecord') && $this->customerRecord) {
            return "{$this->customerRecord->name} ({$this->customer})";
        }

        return $this->customer;
    }

    /**
     * Formatted total amount string.
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return number_format((float) ($this->total_amount ?? 0), 0, ',', '.').' ₫';
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
     * Define the column headers in Google Sheet for Orders.
     * Cấu trúc: Dấu thời gian,Ngày,Mã,Tên đơn/dự án,Khách hàng,Loại,Tổng tiền,Trạng Thái,Link báo giá,Ghi chú,NV Sale,Chiết khấu NV sale,Thanh toán cho sale
     *
     * @return array<int, string>
     */
    public static function sheetHeaders(): array
    {
        return [
            'Dấu thời gian',
            'Ngày',
            'Mã',
            'Tên đơn/dự án',
            'Khách hàng',
            'Loại',
            'Tổng tiền',
            'Trạng Thái',
            'Link báo giá',
            'Ghi chú',
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
            $this->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'), // 0: Dấu thời gian
            $this->order_date?->format('Y-m-d H:i:s') ?? '',                            // 1: Ngày
            $this->code,                                                                // 2: Mã (Key column C)
            $this->name ?? '',                                                          // 3: Tên đơn/dự án
            $this->customer,                                                            // 4: Khách hàng
            $this->type ?? 'Đơn hàng',                                                  // 5: Loại
            (float) ($this->total_amount ?? 0),                                         // 6: Tổng tiền
            $this->status ?? 'Mới',                                                     // 7: Trạng Thái
            $this->quote_url ?? '',                                                     // 8: Link báo giá
            $this->note ?? '',                                                          // 9: Ghi chú
            $this->salesperson ?? '',                                                   // 10: NV Sale
            $this->sale_discount !== null ? (float) $this->sale_discount : '',          // 11: Chiết khấu NV sale
            $this->sale_payment_status ?? 'Chưa thanh toán',                            // 12: Thanh toán cho sale
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
        $orderDateStr = trim((string) ($row[1] ?? ''));
        $orderDate = null;
        if ($orderDateStr !== '') {
            try {
                $orderDate = Carbon::parse($orderDateStr);
            } catch (Throwable) {
                $orderDate = now();
            }
        } else {
            $orderDate = now();
        }

        $rawTotal = str_replace([',', ' ', '₫', 'VND', 'vnd'], '', (string) ($row[6] ?? '0'));
        $rawDiscount = str_replace([',', ' ', '₫', 'VND', 'vnd'], '', (string) ($row[11] ?? ''));

        return [
            'order_date' => $orderDate,
            'code' => trim((string) ($row[2] ?? '')),
            'name' => trim((string) ($row[3] ?? '')) ?: null,
            'customer' => trim((string) ($row[4] ?? '')),
            'type' => in_array(trim((string) ($row[5] ?? '')), self::TYPES, true) ? trim((string) $row[5]) : 'Đơn hàng',
            'total_amount' => is_numeric($rawTotal) ? (float) $rawTotal : 0,
            'status' => in_array(trim((string) ($row[7] ?? '')), self::STATUSES, true) ? trim((string) $row[7]) : 'Mới',
            'quote_url' => trim((string) ($row[8] ?? '')) ?: null,
            'note' => trim((string) ($row[9] ?? '')) ?: null,
            'salesperson' => trim((string) ($row[10] ?? '')) ?: null,
            'sale_discount' => is_numeric($rawDiscount) ? (float) $rawDiscount : null,
            'sale_payment_status' => in_array(trim((string) ($row[12] ?? '')), self::SALE_PAYMENT_STATUSES, true) ? trim((string) $row[12]) : 'Chưa thanh toán',
        ];
    }

    /**
     * Unique identifier for the sheet type.
     */
    public static function sheetType(): string
    {
        return 'order';
    }
}
