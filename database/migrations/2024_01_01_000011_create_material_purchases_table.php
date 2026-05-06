<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->float('quantity');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_rate', 5, 4)->default(0.14975);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('vendor', 255);
            $table->string('vendor_contact', 255)->nullable();
            $table->unsignedBigInteger('receipt_media_id')->nullable();
            $table->boolean('stock_updated')->default(false);
            $table->timestamp('purchased_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_purchases');
    }
};
