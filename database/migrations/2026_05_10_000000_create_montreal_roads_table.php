<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('montreal_roads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->nullable();
            $table->string('source', 100)->default('osm')->index();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE montreal_roads ADD COLUMN geom GEOMETRY(LINESTRING, 4326) NOT NULL');
        DB::statement('CREATE INDEX idx_montreal_roads_geom ON montreal_roads USING GIST(geom)');
        DB::statement('CREATE INDEX idx_montreal_roads_source ON montreal_roads(source)');
    }

    public function down(): void
    {
        Schema::dropIfExists('montreal_roads');
    }
};
