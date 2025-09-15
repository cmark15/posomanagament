<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            //
            if (!Schema::hasColumn('activity_logs', 'subject_type')) {
                $table->string('subject_type')->nullable();
            }
            if (!Schema::hasColumn('activity_logs', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable();
            }
            if (!Schema::hasColumn('activity_logs', 'action')) {
                $table->string('action')->nullable();
            }
        });
        // 2) Ensure indexes exist (Postgres-safe, idempotent)
        // subject_type
        DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_subject_type_index ON activity_logs (subject_type)');
        // subject_id
        DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_subject_id_index ON activity_logs (subject_id)');
        // action
        DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_action_index ON activity_logs (action)');
        // created_at (only if column exists)
        if (Schema::hasColumn('activity_logs', 'created_at')) {
            DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_created_at_index ON activity_logs (created_at)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes first (safe if they don't exist)
        DB::statement('DROP INDEX IF EXISTS activity_logs_subject_type_index');
        DB::statement('DROP INDEX IF EXISTS activity_logs_subject_id_index');
        DB::statement('DROP INDEX IF EXISTS activity_logs_action_index');
        DB::statement('DROP INDEX IF EXISTS activity_logs_created_at_index');
        
        Schema::table('activity_logs', function (Blueprint $table) {
            //
            if (Schema::hasColumn('activity_logs', 'subject_id')) {
                $table->dropColumn('subject_id');
            }
            if (Schema::hasColumn('activity_logs', 'subject_type')) {
                $table->dropColumn('subject_type');
            }
        });
    }
};
