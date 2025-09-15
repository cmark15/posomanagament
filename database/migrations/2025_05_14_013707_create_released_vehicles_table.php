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
        Schema::create('released_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')
            ->constrained('tickets')           // assumes tickets.id
            ->onDelete('cascade');
            $table->string('reference_number', 8);
            $table->timestamp('released_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('released_vehicles');
    }
};
