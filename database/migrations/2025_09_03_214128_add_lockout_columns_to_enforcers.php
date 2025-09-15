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
            if (!Schema::hasColumn('enforcers', 'failed_attempts')) {
                $table->unsignedTinyInteger('failed_attempts')->default(0)->after('password');
            }
            if (!Schema::hasColumn('enforcers', 'lockout_until')) {
                $table->timestamp('lockout_until')->nullable()->after('failed_attempts');
            }
            if (!Schema::hasColumn('enforcers', 'lockouts_count')) {
                $table->unsignedSmallInteger('lockouts_count')->default(0)->after('lockout_until');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enforcers', function (Blueprint $table) {
            //
             if (Schema::hasColumn('enforcers', 'failed_attempts')) $table->dropColumn('failed_attempts');
            if (Schema::hasColumn('enforcers', 'lockout_until')) $table->dropColumn('lockout_until');
            if (Schema::hasColumn('enforcers', 'lockouts_count')) $table->dropColumn('lockouts_count');
        });
    }
};
