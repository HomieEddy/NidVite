<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->unsignedSmallInteger('reliability_score')->nullable()->after('location_source');
            $table->json('reliability_breakdown')->nullable()->after('reliability_score');
            $table->timestamp('reliability_scored_at')->nullable()->after('reliability_breakdown');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropColumn([
                'reliability_score',
                'reliability_breakdown',
                'reliability_scored_at',
            ]);
        });
    }
};
