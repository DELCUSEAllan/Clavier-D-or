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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('theme_id')
                ->constrained('themes')
                ->cascadeOnDelete();

            $table->text('intitule');
            $table->string('type_question', 10);
            $table->string('reponse_correcte', 500);
            $table->string('reponse_fausse_1', 500)->nullable();
            $table->string('reponse_fausse_2', 500)->nullable();
            $table->string('reponse_fausse_3', 500)->nullable();
            $table->string('difficulte', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
