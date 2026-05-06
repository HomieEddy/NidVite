<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('slug', 50)->unique();
            $table->string('label_fr', 100);
            $table->string('label_en', 100)->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('is_inventory_related')->default(false);
            $table->boolean('is_required')->default(false);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
