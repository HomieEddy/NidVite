<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enable PostGIS extension
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reporter_email')->index();
            $table->string('preferred_locale', 5)->default('fr');
            $table->timestamp('email_verified_at')->nullable();
            // location will be added via raw SQL as GEOGRAPHY(POINT,4326)
            $table->float('location_accuracy')->nullable();
            $table->string('address', 500)->nullable();
            $table->string('neighborhood', 100)->nullable()->index();
            $table->string('borough', 100)->nullable()->index();
            $table->enum('status', ['received', 'verified', 'scheduled', 'in_progress', 'repaired', 'rejected'])->default('received')->index();
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal')->index();
            $table->unsignedSmallInteger('category_id')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address_hash', 64)->index();
            $table->string('ip_address_raw', 45)->nullable();
            $table->string('user_agent_hash', 64);
            $table->boolean('geofence_passed')->default(false);
            $table->timestamp('geofence_checked_at')->nullable();
            $table->integer('submission_duration_ms')->nullable();
            $table->boolean('is_spam')->default(false)->index();
            $table->float('spam_score')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('first_scheduled_at')->nullable();
            $table->timestamp('first_started_at')->nullable();
            $table->timestamp('target_completion_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->default(DB::raw("NOW() + INTERVAL '2 years'"));
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('report_categories')->nullOnDelete();
        });

        // Add PostGIS geography column via raw SQL
        DB::statement('ALTER TABLE reports ADD COLUMN location GEOGRAPHY(POINT,4326) NOT NULL');
        DB::statement('CREATE INDEX idx_reports_location_gist ON reports USING GIST(location)');
        DB::statement('CREATE INDEX idx_reports_created_at ON reports(created_at DESC)');
        DB::statement('CREATE INDEX idx_reports_status_created ON reports(status, created_at DESC)');
        DB::statement('CREATE INDEX idx_reports_email_created ON reports(reporter_email, created_at DESC)');
        DB::statement('CREATE INDEX idx_reports_neighborhood ON reports(neighborhood, status)');
        DB::statement('CREATE INDEX idx_reports_deleted_at ON reports(deleted_at) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
