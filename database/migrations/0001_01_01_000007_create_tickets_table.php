<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('reference_id');
            $table->string('title');
            $table->longText('content');
            $table->foreignId('type_id')->constrained('ticket_types');
            $table->foreignId('status_id')->constrained('ticket_statuses');
            $table->string('priority')->default('medium');
            $table->json('custom_fields')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'number']);
            $table->unique('reference_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
