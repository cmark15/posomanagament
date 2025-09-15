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
        Schema::table('tickets', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('status_id')
              ->nullable()
              ->after('issued_at');
            $table->unsignedBigInteger('confiscation_type_id')
              ->nullable()
              ->after('status_id');
        });
        DB::table('tickets')->update([
            'status_id'             => 1, // assuming ticket_statuses.id=1 is “pending”
            'confiscation_type_id'  => 1, // assuming confiscation_types.id=1 is “None”
        ]);
        Schema::table('tickets', function (Blueprint $t) {
            // 3a) now make them not-nullable
            $t->unsignedBigInteger('status_id')
              ->nullable(false)
              ->change();
            $t->unsignedBigInteger('confiscation_type_id')
              ->nullable(false)
              ->change();

            // 3b) add the foreign keys
            $t->foreign('status_id')
              ->references('id')
              ->on('ticket_statuses')
              ->cascadeOnDelete();
            $t->foreign('confiscation_type_id')
              ->references('id')
              ->on('confiscation_types')
              ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            //
            $table->dropForeign(['status_id']);
            $table->dropForeign(['confiscation_type_id']);
            $table->dropColumn(['status_id','confiscation_type_id']);
        });
    }
};
