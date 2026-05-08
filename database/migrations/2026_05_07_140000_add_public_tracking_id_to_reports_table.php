<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->string('public_tracking_id', 11)->nullable()->unique()->after('uuid');
        });

        $existing = DB::table('reports')
            ->select('id')
            ->whereNull('public_tracking_id')
            ->pluck('id');

        foreach ($existing as $id) {
            do {
                $candidate = 'MTL'.strtoupper(Str::random(8));
                $exists = DB::table('reports')
                    ->where('public_tracking_id', $candidate)
                    ->exists();
            } while ($exists);

            DB::table('reports')
                ->where('id', $id)
                ->update(['public_tracking_id' => $candidate]);
        }

        DB::statement('ALTER TABLE reports ALTER COLUMN public_tracking_id SET NOT NULL');
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropUnique(['public_tracking_id']);
            $table->dropColumn('public_tracking_id');
        });
    }
};
