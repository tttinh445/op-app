<?php

namespace App\Contracts;

interface GoogleSheetSyncable
{
    /**
     * Define the column headers in Google Sheet.
     *
     * @return array<int, string>
     */
    public static function sheetHeaders(): array;

    /**
     * Map model attributes to Google Sheet row values.
     *
     * @return array<int, mixed>
     */
    public function toSheetRow(): array;

    /**
     * Map raw row data from Google Sheet into model attributes.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public static function fromSheetRow(array $row): array;

    /**
     * Unique identifier for the sheet type.
     */
    public static function sheetType(): string;
}
