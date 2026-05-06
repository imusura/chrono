<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreignId('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->string('submitter_email')->nullable()->after('assigned_to');
            $table->string('submitter_name')->nullable()->after('submitter_email');
            $table->json('metadata')->nullable()->after('submitter_name');
            $table->foreignId('created_via_api_client_id')->nullable()->after('metadata')
                ->constrained('api_clients')->nullOnDelete();
            $table->string('idempotency_key')->nullable()->after('created_via_api_client_id');

            $table->unique(['created_via_api_client_id', 'idempotency_key'], 'tickets_api_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique('tickets_api_idempotency_unique');
            $table->dropConstrainedForeignId('created_via_api_client_id');
            $table->dropColumn(['submitter_email', 'submitter_name', 'metadata', 'idempotency_key']);

            $table->dropForeign(['created_by']);
            $table->foreignId('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users');
        });
    }
};
