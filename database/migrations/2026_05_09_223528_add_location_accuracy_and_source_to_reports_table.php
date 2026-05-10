<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->float('location_accuracy')->nullable()->after('location');
            $table->string('location_source', 20)->nullable()->after('location_accuracy')
                ->comment('gps|manual|geocode');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex($table->getName());
        });

        DB::statement('ALTER TABLE reports ADD CONSTRAINT chk_location_accuracy_non_negative CHECK (location_accuracy IS NULL OR location_accuracy >= 0)');
        DB::statement("ALTER TABLE reports ADD CONSTRAINT chk_location_source_allowed CHECK (location_source IS NULL OR location_source IN ('gps', 'manual', 'geocode'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['location_accuracy', 'location_source']);
        });
    }
};
