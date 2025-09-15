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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id('vehicle_id');
            $table->unsignedBigInteger('violator_id');
            $table->string('plate_number')->unique();
            $table->string('owner_name')->nullable();
            $table->enum('vehicle_type',[
                'Motorcycle',
                'Tricycle',
                'Truck',
                'Sedan',
                'Bus',
                'Jeepney',
                'Van',
                'SUV',
                'Pickup',
            ]);
            $table->boolean('is_owner')->default(true);
            $table->timestamps();
            
            $table->foreign('violator_id')->references('id')->on('violators')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
