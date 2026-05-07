<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('non_working_days', function (Blueprint $table) {
            // Drop the existing unique constraint before restructuring
            $table->dropUnique(['organisation_id', 'date']);

            $table->string('country_code', 2)->nullable()->after('organisation_id');

            // Public holidays: unique per country + date (organisation_id IS NULL)
            // Custom days: unique per organisation + date (handled by app logic)
            $table->unique(['country_code', 'date', 'organisation_id']);
        });
    }

    public function down(): void
    {
        Schema::table('non_working_days', function (Blueprint $table) {
            $table->dropUnique(['country_code', 'date', 'organisation_id']);
            $table->dropColumn('country_code');
            $table->unique(['organisation_id', 'date']);
        });
    }
};
