<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove clutter columns from all business tables.
     */
    public function up(): void
    {
        // users: email_verified_at is not needed for admin dashboard
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });

        // repair_jobs: weather_conditions is nice-to-have but not essential
        Schema::table('repair_jobs', function (Blueprint $table) {
            $table->dropColumn('weather_conditions');
        });

        // job_reports: pivot table should stay minimal
        Schema::table('job_reports', function (Blueprint $table) {
            $table->dropColumn(['cost_override_reason', 'repair_notes']);
        });

        // expenses: vendor_contact and receipt_media_id are not MVP
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['vendor_contact', 'receipt_media_id']);
        });

        // material_purchases: vendor_contact and receipt_media_id are not MVP
        Schema::table('material_purchases', function (Blueprint $table) {
            $table->dropColumn(['vendor_contact', 'receipt_media_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('password');
        });

        Schema::table('repair_jobs', function (Blueprint $table) {
            $table->string('weather_conditions', 255)->nullable()->after('actual_cost');
        });

        Schema::table('job_reports', function (Blueprint $table) {
            $table->string('cost_override_reason', 255)->nullable()->after('cost_allocation_percentage');
            $table->text('repair_notes')->nullable()->after('cost_override_reason');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('vendor_contact', 255)->nullable()->after('vendor');
            $table->unsignedBigInteger('receipt_media_id')->nullable()->after('vendor_contact');
        });

        Schema::table('material_purchases', function (Blueprint $table) {
            $table->string('vendor_contact', 255)->nullable()->after('vendor');
            $table->unsignedBigInteger('receipt_media_id')->nullable()->after('vendor_contact');
        });
    }
};
