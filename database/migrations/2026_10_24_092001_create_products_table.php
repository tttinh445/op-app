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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->default('Cái');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->text('note')->nullable();
            $table->foreignId('google_sheet_config_id')->nullable()->constrained('google_sheet_configs')->nullOnDelete();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
