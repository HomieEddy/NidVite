<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->decimal('road_distance_meters', 10, 2)->nullable()->after('location_source');
            $table->string('road_validation_decision', 50)->nullable()->after('road_distance_meters');
            $table->string('road_validation_reason', 100)->nullable()->after('road_validation_decision');
            $table->string('road_validation_mode', 20)->nullable()->after('road_validation_reason');
            $table->boolean('location_accuracy_passed')->nullable()->after('road_validation_mode');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn([
                'road_distance_meters',
                'road_validation_decision',
                'road_validation_reason',
                'road_validation_mode',
                'location_accuracy_passed',
            ]);
        });
    }
};
