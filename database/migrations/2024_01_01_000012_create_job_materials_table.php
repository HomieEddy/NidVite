<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->float('quantity_planned')->default(0);
            $table->float('quantity_actual')->nullable();
            $table->decimal('unit_cost_at_time', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['repair_job_id', 'material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_materials');
    }
};
