<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('notification_preference', 20)
                ->default('all')
                ->after('preferred_locale')
                ->index();
        });

        Schema::create('report_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('preferred_locale', 5)->default('fr');
            $table->boolean('is_active')->default(true);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->date('last_notified_on')->nullable();
            $table->timestamps();

            $table->unique(['report_id', 'email']);
            $table->index(['report_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_followers');

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['notification_preference']);
            $table->dropColumn('notification_preference');
        });
    }
};
