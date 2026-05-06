<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropForeign(['status_id']);

            $table->foreign('type_id')->references('id')->on('ticket_types')->cascadeOnDelete();
            $table->foreign('status_id')->references('id')->on('ticket_statuses')->cascadeOnDelete();
        });

        Schema::table('api_clients', function (Blueprint $table) {
            $table->dropForeign(['default_ticket_type_id']);

            $table->foreign('default_ticket_type_id')->references('id')->on('ticket_types')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropForeign(['status_id']);

            $table->foreign('type_id')->references('id')->on('ticket_types');
            $table->foreign('status_id')->references('id')->on('ticket_statuses');
        });

        Schema::table('api_clients', function (Blueprint $table) {
            $table->dropForeign(['default_ticket_type_id']);

            $table->foreign('default_ticket_type_id')->references('id')->on('ticket_types')->restrictOnDelete();
        });
    }
};
