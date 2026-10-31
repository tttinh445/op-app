<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $employees = [
            ['code' => 'NV-1', 'name' => 'Quy', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'NV-2', 'name' => 'Dung', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'NV-3', 'name' => 'Binh', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($employees as $employee) {
            DB::table('employees')->updateOrInsert(
                ['code' => $employee['code']],
                $employee
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('employees')->whereIn('code', ['NV-1', 'NV-2', 'NV-3'])->delete();
    }
};
