<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progression_niveaux', function (Blueprint $table) {
            $table->id();

            $table->foreignId('utilisateur_id')
                ->constrained('utilisateurs')
                ->cascadeOnDelete();

            $table->foreignId('theme_id')
                ->constrained('themes')
                ->cascadeOnDelete();

            $table->unsignedInteger('meilleur_score')->default(0);

            $table->boolean('est_deverrouille')->default(false);

            $table->timestamp('reussi_le')->nullable();

            $table->timestamps();

            $table->unique([
                'utilisateur_id',
                'theme_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progression_niveaux');
    }
};
