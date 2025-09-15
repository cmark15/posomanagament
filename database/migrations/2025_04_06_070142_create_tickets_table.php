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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enforcer_id');
            $table->unsignedBigInteger('violator_id');
            $table->unsignedBigInteger('vehicle_id')->nullable(); 
            $table->json('violation_codes');
            $table->string('location');
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();
            
            $table->foreign('enforcer_id')->references('id')->on('enforcers')->onDelete('cascade');
            $table->foreign('violator_id')->references('id')->on('violators')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
