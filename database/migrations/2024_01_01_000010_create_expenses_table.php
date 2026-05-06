<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->cascadeOnDelete();
            $table->unsignedSmallInteger('category_id');
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->string('description', 500);
            $table->float('quantity')->default(1);
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('tax_rate', 5, 4)->default(0.14975);
            $table->decimal('tax_amount', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->string('vendor', 255)->nullable();
            $table->string('vendor_contact', 255)->nullable();
            $table->unsignedBigInteger('receipt_media_id')->nullable();
            $table->timestamp('incurred_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('expense_categories');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
