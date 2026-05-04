<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->cascadeOnDelete();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->float('cost_allocation_percentage')->default(0);
            $table->string('cost_override_reason', 255)->nullable();
            $table->text('repair_notes')->nullable();
            $table->timestamps();

            $table->unique(['repair_job_id', 'report_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_reports');
    }
};
