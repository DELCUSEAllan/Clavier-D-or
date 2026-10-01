<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();

            $table->foreignId('utilisateur_id')
                ->constrained('utilisateurs')
                ->cascadeOnDelete();

            $table->string('role', 20);

            $table->unsignedTinyInteger('niveau_actuel')->default(1);
            $table->unsignedTinyInteger('erreurs_niveau')->default(0);
            $table->unsignedTinyInteger('bonnes_reponses_niveau')->default(0);
            $table->unsignedTinyInteger('jokers_restants')->default(1);

            $table->unsignedInteger('score_total')->default(0);
            $table->unsignedInteger('questions_correctes_total')->default(0);

            $table->string('statut', 20)->default('en_cours');
            $table->timestamp('date_fin')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
