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
        Schema::table('enforcers', function (Blueprint $table) {
            //
            $table->unsignedInteger('ticket_start')->default(0)->after('phone');
            $table->unsignedInteger('ticket_end')->default(0)->after('ticket_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enforcers', function (Blueprint $table) {
            //
            $table->dropColumn(['ticket_start','ticket_end']);
        });
    }
};
