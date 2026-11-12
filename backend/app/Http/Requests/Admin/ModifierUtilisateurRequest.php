<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModifierUtilisateurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $utilisateur = $this->route('utilisateur');

        return [
            'pseudo' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('utilisateurs', 'pseudo')
                    ->ignore($utilisateur),
            ],

            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('utilisateurs', 'email')
                    ->ignore($utilisateur),
            ],

            'mot_de_passe' => [
                'sometimes',
                'string',
                'min:8',
                'confirmed',
            ],

            'email_verifie_le' => [
                'sometimes',
                'nullable',
                'date',
            ],

            'meilleur_score' => [
                'sometimes',
                'integer',
                'min:0',
                'max:55000',
            ],

            'est_admin' => [
                'sometimes',
                'boolean',
            ],

            'id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'jeton_souvenir' => ['prohibited'],
        ];
    }
}
