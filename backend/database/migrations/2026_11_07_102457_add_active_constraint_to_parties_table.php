<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->unsignedTinyInteger(
                'verrou_partie_active'
            )
                ->nullable()
                ->storedAs(
                    "CASE
                        WHEN statut IN (
                            'en_cours',
                            'niveau_reussi'
                        )
                        THEN 1
                        ELSE NULL
                    END"
                );

            $table->unique(
                [
                    'utilisateur_id',
                    'verrou_partie_active',
                ],
                'parties_utilisateur_active_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropUnique(
                'parties_utilisateur_active_unique'
            );

            $table->dropColumn('verrou_partie_active');
        });
    }
};
