<?php

namespace App\Models;

use Database\Factories\PendingSheetSyncFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingSheetSync extends Model
{
    /** @use HasFactory<PendingSheetSyncFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sheet_type',
        'action',
        'code',
        'original_code',
        'name',
        'google_sheet_config_id',
        'row_data',
        'status',
        'error_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'row_data' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Associated Google Sheet configuration.
     */
    public function googleSheetConfig(): BelongsTo
    {
        return $this->belongsTo(GoogleSheetConfig::class);
    }
}
