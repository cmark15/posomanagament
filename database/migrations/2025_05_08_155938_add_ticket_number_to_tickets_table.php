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
        Schema::table('tickets', function (Blueprint $table) {
            //
            $table->unsignedInteger('ticket_number')->nullable()->after('id');
            $table->unique(['enforcer_id','ticket_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            //
            $table->dropUnique(['enforcer_id','ticket_number']);
            $table->dropColumn('ticket_number');
        });
    }
};
