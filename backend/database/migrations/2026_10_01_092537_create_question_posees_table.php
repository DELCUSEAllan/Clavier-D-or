<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions_posees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('partie_id')
                ->constrained('parties')
                ->cascadeOnDelete();

            $table->foreignId('question_id')
                ->constrained('questions')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('ordre');
            $table->string('reponse_donnee', 500)->nullable();
            $table->boolean('est_correcte')->nullable();
            $table->boolean('joker_utilise')->default(false);

            $table->timestamps();

            $table->unique(['partie_id', 'question_id']);
            $table->unique(['partie_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions_posees');
    }
};
