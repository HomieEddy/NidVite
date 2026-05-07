<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reports', 'ip_address_raw')) {
                $table->string('ip_address_raw', 45)->nullable()->after('description');
            }

            if (! Schema::hasColumn('reports', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('completed_at')->index();
            }

            if (! Schema::hasColumn('reports', 'archive_path')) {
                $table->string('archive_path', 512)->nullable()->after('archived_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'archive_path')) {
                $table->dropColumn('archive_path');
            }

            if (Schema::hasColumn('reports', 'archived_at')) {
                $table->dropColumn('archived_at');
            }

            if (Schema::hasColumn('reports', 'ip_address_raw')) {
                $table->dropColumn('ip_address_raw');
            }
        });
    }
};
