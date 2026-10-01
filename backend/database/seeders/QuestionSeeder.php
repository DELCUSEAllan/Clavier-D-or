<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $chemin = database_path('data/questions.json');

        $questions = json_decode(
            file_get_contents($chemin),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($questions as $question) {
            Question::updateOrCreate(
                [
                    'theme_id' => $question['theme_id'],
                    'intitule' => $question['intitule'],
                ],
                $question
            );
        }
    }
}
