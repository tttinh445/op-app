<?php

namespace App\Models;

use Database\Factories\GoogleSheetConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleSheetConfig extends Model
{
    /** @use HasFactory<GoogleSheetConfigFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'spreadsheet_url',
        'spreadsheet_id',
        'sheet_name',
        'sheet_type',
        'gid',
        'range',
        'primary_key_column',
        'api_key',
        'apps_script_url',
        'sync_mode',
        'is_active',
        'last_synced_at',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Associated customers.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Associated suppliers.
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Associated products.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Parse and extract spreadsheet ID from a given URL or return raw ID.
     */
    public static function extractSpreadsheetId(string $urlOrId): string
    {
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $urlOrId, $matches)) {
            return $matches[1];
        }

        return trim($urlOrId);
    }

    /**
     * Parse and extract gid from a Google Sheets URL if present.
     */
    public static function extractGid(string $url): ?string
    {
        if (preg_match('/[#&]gid=([0-9]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Build the public CSV export URL for reading without API key.
     */
    public function getPublicCsvUrl(): string
    {
        $id = $this->spreadsheet_id;

        if ($this->gid !== null && $this->gid !== '') {
            return "https://docs.google.com/spreadsheets/d/{$id}/export?format=csv&gid={$this->gid}";
        }

        $sheetParam = urlencode($this->sheet_name ?: 'Sheet1');

        return "https://docs.google.com/spreadsheets/d/{$id}/gviz/tq?tqx=out:csv&sheet={$sheetParam}";
    }

    /**
     * Convert the primary key column (e.g. 'A', 'B', 'AA') to 0-based column index.
     * Defaults to 0 (Column A) if not specified or invalid.
     */
    public function getKeyColumnIndex(): int
    {
        $val = trim((string) ($this->primary_key_column ?? 'A'));
        if ($val === '') {
            return 0;
        }

        if (is_numeric($val)) {
            return max(0, (int) $val);
        }

        $upper = strtoupper($val);
        if (preg_match('/^[A-Z]+$/', $upper)) {
            $index = 0;
            $len = strlen($upper);
            for ($i = 0; $i < $len; $i++) {
                $index = $index * 26 + (ord($upper[$i]) - ord('A') + 1);
            }

            return max(0, $index - 1);
        }

        return 0;
    }

    /**
     * Get clean uppercase primary key column letter (default: 'A').
     */
    public function getKeyColumnLetter(): string
    {
        $val = strtoupper(trim((string) ($this->primary_key_column ?? 'A')));

        return $val !== '' ? $val : 'A';
    }
}
