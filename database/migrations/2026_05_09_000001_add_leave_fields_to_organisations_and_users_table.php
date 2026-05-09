<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->string('vacation_mode')->default('simple');
            $table->string('year_reset_date')->default('01-01');
            $table->unsignedSmallInteger('carryover_max_days')->nullable();
            $table->unsignedTinyInteger('carryover_expiry_months')->nullable();
            $table->unsignedSmallInteger('last_reset_year')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('vacation_days')->default(20);
        });
    }

    public function down(): void
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->dropColumn([
                'vacation_mode',
                'year_reset_date',
                'carryover_max_days',
                'carryover_expiry_months',
                'last_reset_year',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('vacation_days');
        });
    }
};
