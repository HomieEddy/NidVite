<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('device_fingerprint_hash', 64)->nullable()->after('description');
            $table->index('device_fingerprint_hash');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['device_fingerprint_hash']);
            $table->dropColumn('device_fingerprint_hash');
        });
    }
};

