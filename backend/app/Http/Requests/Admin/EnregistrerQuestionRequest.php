<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnregistrerQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme_id' => [
                'required',
                'integer',
                'exists:themes,id',
            ],
            'intitule' => [
                'required',
                'string',
                'max:2000',
            ],
            'type_question' => [
                'required',
                Rule::in(['QCM', 'VF']),
            ],
            'reponse_correcte' => [
                'required',
                'string',
                'max:500',
            ],
            'reponse_fausse_1' => [
                'required',
                'string',
                'max:500',
                'different:reponse_correcte',
            ],
            'reponse_fausse_2' => [
                'nullable',
                'required_if:type_question,QCM',
                'string',
                'max:500',
                'different:reponse_correcte,reponse_fausse_1',
            ],
            'reponse_fausse_3' => [
                'nullable',
                'required_if:type_question,QCM',
                'string',
                'max:500',
                'different:reponse_correcte,reponse_fausse_1,reponse_fausse_2',
            ],
            'difficulte' => [
                'required',
                Rule::in([
                    'Facile',
                    'Moyenne',
                    'Difficile',
                    'Extreme',
                ]),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('type_question') === 'VF') {
            $this->merge([
                'reponse_fausse_2' => null,
                'reponse_fausse_3' => null,
            ]);
        }
    }
}
