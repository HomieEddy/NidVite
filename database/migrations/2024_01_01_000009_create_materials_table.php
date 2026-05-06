<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 50);
            $table->float('current_stock')->default(0);
            $table->float('reserved_stock')->default(0);
            $table->float('min_stock_alert')->default(0);
            $table->decimal('avg_purchase_price', 10, 2)->nullable();
            $table->decimal('last_purchase_price', 10, 2)->nullable();
            $table->string('location', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
