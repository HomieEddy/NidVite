<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('montreal_boundary', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE montreal_boundary ADD COLUMN boundary GEOMETRY(POLYGON, 4326)');
        DB::statement('CREATE INDEX idx_montreal_boundary_boundary ON montreal_boundary USING GIST(boundary)');
    }

    public function down(): void
    {
        Schema::dropIfExists('montreal_boundary');
    }
};
