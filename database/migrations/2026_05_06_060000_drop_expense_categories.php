<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only drop the constraint if it exists (for backwards compat)
        DB::statement(
            "DO $$ BEGIN\n".
            "  IF EXISTS (SELECT 1 FROM information_schema.table_constraints\n".
            "    WHERE constraint_name = 'expenses_category_id_foreign'\n".
            "    AND table_name = 'expenses') THEN\n".
            "    ALTER TABLE expenses DROP CONSTRAINT expenses_category_id_foreign;\n".
            "  END IF;\n".
            'END $$;'
        );

        if (Schema::hasColumn('expenses', 'category_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('category_id');
            });
        }

        Schema::dropIfExists('expense_categories');
    }

    public function down(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('label_fr', 100);
            $table->string('label_en', 100)->nullable();
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedSmallInteger('category_id')->after('repair_job_id');
            $table->foreign('category_id')->references('id')->on('expense_categories');
        });
    }
};
