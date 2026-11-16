<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnregistrerThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $theme = $this->route('theme');

        $presence = $this->isMethod('post')
            ? 'required'
            : 'sometimes';

        return [
            'nom' => [
                $presence,
                'string',
                'max:50',
                Rule::unique('themes', 'nom')
                    ->ignore($theme),
            ],

            'numero_niveau' => [
                $presence,
                'integer',
                'between:1,6',
                Rule::unique('themes', 'numero_niveau')
                    ->ignore($theme),
            ],

            'id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
