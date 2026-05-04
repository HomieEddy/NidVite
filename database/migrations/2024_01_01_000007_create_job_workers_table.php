<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role_in_job', ['lead', 'assistant'])->default('assistant');
            $table->float('hours_worked')->nullable();
            $table->timestamps();

            $table->unique(['repair_job_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_workers');
    }
};
