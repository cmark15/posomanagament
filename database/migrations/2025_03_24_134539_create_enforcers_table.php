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
        Schema::create('enforcers', function (Blueprint $table) {
            $table->id();
            $table->string('badge_num', 15)->unique();
            $table->string('fname', 20);
            $table->string('mname', 20);
            $table->string('lname', 20);
            $table->string('phone', 15)->unique();
            $table->string('password', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enforcers');
    }
};
