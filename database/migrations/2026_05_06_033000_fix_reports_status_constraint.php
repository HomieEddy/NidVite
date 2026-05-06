<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Fix reports table status check constraint to match ReportStatus enum.
     *
     * The original migration created a constraint with 'pending' instead of 'received',
     * and was missing 'verified'. This migration corrects the constraint and default.
     */
    public function up(): void
    {
        // Drop the incorrect constraint
        DB::statement('ALTER TABLE reports DROP CONSTRAINT IF EXISTS reports_status_check');

        // Update default value
        DB::statement("ALTER TABLE reports ALTER COLUMN status SET DEFAULT 'received'");

        // Update existing records
        DB::statement("UPDATE reports SET status = 'received' WHERE status = 'pending'");

        // Add correct constraint matching ReportStatus enum
        DB::statement(
            "ALTER TABLE reports ADD CONSTRAINT reports_status_check
            CHECK (status::text = ANY (ARRAY[
                'received'::character varying,
                'verified'::character varying,
                'scheduled'::character varying,
                'in_progress'::character varying,
                'repaired'::character varying,
                'rejected'::character varying
            ]::text[]))"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE reports DROP CONSTRAINT IF EXISTS reports_status_check');
        DB::statement("ALTER TABLE reports ALTER COLUMN status SET DEFAULT 'pending'");
        DB::statement("UPDATE reports SET status = 'pending' WHERE status IN ('received', 'verified')");
        DB::statement(
            "ALTER TABLE reports ADD CONSTRAINT reports_status_check
            CHECK (status::text = ANY (ARRAY[
                'pending'::character varying,
                'scheduled'::character varying,
                'in_progress'::character varying,
                'repaired'::character varying,
                'rejected'::character varying
            ]::text[]))"
        );
    }
};
