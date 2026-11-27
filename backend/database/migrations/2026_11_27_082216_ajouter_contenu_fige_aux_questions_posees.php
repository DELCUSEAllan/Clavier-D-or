<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions_posees', function (Blueprint $table) {
            $table->json('contenu_fige')->nullable();
        });

        DB::table('questions_posees as qp')
            ->join('questions as q', 'q.id', '=', 'qp.question_id')
            ->select([
                'qp.id as pose_id',
                'q.theme_id',
                'q.intitule',
                'q.type_question',
                'q.difficulte',
                'q.reponse_correcte',
                'q.reponse_fausse_1',
                'q.reponse_fausse_2',
                'q.reponse_fausse_3',
            ])
            ->chunkById(200, function ($lignes): void {
                foreach ($lignes as $ligne) {
                    $contenu = (array) $ligne;
                    unset($contenu['pose_id']);

                    DB::table('questions_posees')
                        ->where('id', $ligne->pose_id)
                        ->update([
                            'contenu_fige' => json_encode($contenu, JSON_THROW_ON_ERROR),
                        ]);
                }
            }, 'qp.id', 'pose_id');
    }

    public function down(): void
    {
        Schema::table('questions_posees', function (Blueprint $table) {
            $table->dropColumn('contenu_fige');
        });
    }
};
