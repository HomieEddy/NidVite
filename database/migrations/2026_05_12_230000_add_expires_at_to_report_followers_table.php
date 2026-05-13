<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_followers', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('last_notified_on');
            $table->index(['is_active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('report_followers', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};
