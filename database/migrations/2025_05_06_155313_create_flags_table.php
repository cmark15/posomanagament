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
        Schema::create('flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();    // e.g. is_resident, is_impounded
            $table->string('label');
            $table->timestamps();
        });
        DB::table('flags')->insert([
            ['key'=>'is_resident',   'label'=>'Resident'],
            ['key'=>'is_impounded',  'label'=>'Vehicle Impounded'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flags');
    }
};
