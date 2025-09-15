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
        Schema::create('ticket_flags', function (Blueprint $table) {
            $table->foreignId('ticket_id')
                  ->constrained('tickets')
                  ->cascadeOnDelete();
            $table->foreignId('flag_id')
                  ->constrained('flags')
                  ->cascadeOnDelete();
            $table->primary(['ticket_id','flag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_flags');
    }
};
