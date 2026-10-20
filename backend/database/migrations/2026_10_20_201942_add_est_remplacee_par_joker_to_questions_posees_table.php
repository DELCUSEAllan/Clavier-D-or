<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions_posees', function (Blueprint $table) {
            $table->boolean('est_remplacee_par_joker')
                ->default(false)
                ->after('joker_utilise');
        });
    }

    public function down(): void
    {
        Schema::table('questions_posees', function (Blueprint $table) {
            $table->dropColumn('est_remplacee_par_joker');
        });
    }
};
