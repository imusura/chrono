<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_type_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_status_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order');
            $table->boolean('is_final')->default(false);
            $table->timestamps();

            $table->unique(['ticket_type_id', 'ticket_status_id']);
            $table->index(['ticket_type_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_type_statuses');
    }
};
