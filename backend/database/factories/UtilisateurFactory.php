<?php

namespace Database\Factories;

use App\Models\Utilisateur;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Utilisateur>
 */
class UtilisateurFactory extends Factory
{
    protected $model = Utilisateur::class;

    /**
     * Mot de passe commun utilisé pendant les tests.
     */
    protected static ?string $motDePasse;

    /**
     * Définit les données d’un utilisateur de test.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pseudo' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verifie_le' => now(),
            'mot_de_passe' => static::$motDePasse ??=
                Hash::make('password'),
            'meilleur_score' => 0,
            'est_admin' => false,
            'jeton_souvenir' => Str::random(10),
        ];
    }

    /**
     * Crée un utilisateur dont l’adresse email n’est pas vérifiée.
     */
    public function nonVerifie(): static
    {
        return $this->state(fn (array $attributs) => [
            'email_verifie_le' => null,
        ]);
    }
}
