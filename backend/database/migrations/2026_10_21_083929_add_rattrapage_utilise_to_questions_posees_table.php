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
        Schema::table('questions_posees', function (Blueprint $table) {
            $table->boolean('rattrapage_utilise')
                ->default(false)
                ->after('est_remplacee_par_joker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions_posees', function (Blueprint $table) {
            $table->dropColumn('rattrapage_utilise');
        });
    }
};
