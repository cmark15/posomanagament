<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('paid_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->string('reference_number')->unique();
            $table->timestamp('paid_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paid_tickets');
    }
};
