<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EnregistrerQuestionRequest;
use App\Models\Question;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    public function afficher(): JsonResponse
    {
        $questions = Question::query()
            ->with('theme:id,nom,numero_niveau')
            ->orderBy('theme_id')
            ->orderBy('difficulte')
            ->paginate(20);

        return response()->json($questions);
    }

    public function creer(
        EnregistrerQuestionRequest $request
    ): JsonResponse {
        $question = Question::create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Question créée.',
            'question' => $question->load('theme'),
        ], 201);
    }

    public function modifier(
        EnregistrerQuestionRequest $request,
        Question $question
    ): JsonResponse {
        $question->update(
            $request->validated()
        );

        return response()->json([
            'message' => 'Question modifiée.',
            'question' => $question->load('theme'),
        ]);
    }

    public function supprimer(
        Question $question
    ): JsonResponse {
        $question->delete();

        return response()->json([
            'message' => 'Question supprimée.',
        ]);
    }
}
