<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove clutter columns from reports table.
     *
     * These fields tracked IPs, user agents, timing, and scoring but provided
     * no value to the core pothole-reporting workflow.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex('reports_ip_address_hash_index');
            $table->dropColumn([
                'ip_address_hash',
                'ip_address_raw',
                'user_agent_hash',
                'submission_duration_ms',
                'spam_score',
                'geofence_checked_at',
                'email_verified_at',
                'location_accuracy',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('ip_address_hash', 64)->nullable()->after('description');
            $table->string('ip_address_raw', 45)->nullable()->after('ip_address_hash');
            $table->string('user_agent_hash', 64)->nullable()->after('ip_address_raw');
            $table->integer('submission_duration_ms')->nullable()->after('geofence_passed');
            $table->float('spam_score')->nullable()->after('is_spam');
            $table->timestamp('geofence_checked_at')->nullable()->after('geofence_passed');
            $table->timestamp('email_verified_at')->nullable()->after('preferred_locale');
            $table->float('location_accuracy')->nullable()->after('email_verified_at');
            $table->index('ip_address_hash');
        });
    }
};
