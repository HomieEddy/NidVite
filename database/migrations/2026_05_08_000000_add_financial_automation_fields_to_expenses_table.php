<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->decimal('gst_rate', 5, 4)->default(0.0500)->after('tax_rate');
            $table->decimal('qst_rate', 5, 4)->default(0.0998)->after('gst_rate');
            $table->string('cost_allocation_mode', 30)->default('equal_split')->after('qst_rate');
            $table->string('receipt_path')->nullable()->after('cost_allocation_mode');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropColumn(['gst_rate', 'qst_rate', 'cost_allocation_mode', 'receipt_path']);
        });
    }
};
