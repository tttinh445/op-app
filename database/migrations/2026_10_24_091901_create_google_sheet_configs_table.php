<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('google_sheet_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('spreadsheet_url');
            $table->string('spreadsheet_id');
            $table->string('sheet_name')->default('Trang tính 1');
            $table->string('sheet_type')->default('customer'); // customer, supplier, product, custom
            $table->string('gid')->nullable();
            $table->string('range')->default('A1:Z');
            $table->string('api_key')->nullable();
            $table->text('apps_script_url')->nullable();
            $table->string('sync_mode')->default('csv_public'); // csv_public, api_key, apps_script, local
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_sheet_configs');
    }
};
